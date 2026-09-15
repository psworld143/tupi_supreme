<?php
require_once 'includes/config.php';
$current_page = 'products';

// Get dynamic content
$page_header_title = getPageContent('products', 'page_header_title', 'Our Products');
$page_header_subtitle = getPageContent('products', 'page_header_subtitle', 'Premium activated carbon solutions for municipal water treatment and industrial applications');
$section_title = getPageContent('products', 'section_title', 'Our Products');
$section_subtitle = getPageContent('products', 'section_subtitle', 'Premium activated carbon solutions and sustainable growing mediums');
$specifications_title = getPageContent('products', 'specifications_title', 'Granulated Activated Carbon Specifications');
$specifications_subtitle = getPageContent('products', 'specifications_subtitle', 'Technical specifications for our premium 2mm granulated activated carbon');
$applications_title = getPageContent('products', 'applications_title', 'Applications');
$applications_subtitle = getPageContent('products', 'applications_subtitle', 'Our 2mm granulated activated carbon serves diverse industries, with Municipal Water Treatment as our primary application');
$cta_title = getPageContent('products', 'cta_title', 'Ready to Get Started?');
$cta_description = getPageContent('products', 'cta_description', 'Our technical team specializes in municipal water treatment applications and can help you find the perfect 2mm granulated activated carbon solution for your facility.');
$cta_button_1_text = getPageContent('products', 'cta_button_1_text', 'Request Technical Consultation');
$cta_button_1_link = getPageContent('products', 'cta_button_1_link', 'contact.php');
$cta_button_2_text = getPageContent('products', 'cta_button_2_text', 'Download Technical Specs');
$cta_button_2_link = getPageContent('products', 'cta_button_2_link', 'resources.php');

// Get products from database
$all_products = getProducts();
$granulated_products = array_filter($all_products, function($p) { return stripos($p['slug'], 'granulated') !== false || stripos($p['name'], 'Granulated') !== false; });
$husk_products = array_filter($all_products, function($p) { return stripos($p['slug'], 'husk') !== false || stripos($p['slug'], 'coconut') !== false || stripos($p['name'], 'Coconut') !== false; });
$custom_products = array_filter($all_products, function($p) { return stripos($p['slug'], 'custom') !== false || stripos($p['name'], 'Custom') !== false; });

// Get the main GAC product (first granulated product or use default)
$gac_product = !empty($granulated_products) ? reset($granulated_products) : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Premium 2mm granulated activated carbon for municipal water treatment facilities. Granulated Activated Carbon (GAC), Coconut Husk Products, and custom formulations. Serving 200+ municipal clients.">
    <meta name="keywords" content="2mm granulated activated carbon, municipal water treatment activated carbon, granulated activated carbon GAC, coconut husk products, activated carbon specifications">
    <title>Products - Granulated Activated Carbon & Coconut Husk Products | <?php echo htmlspecialchars_safe(getSiteSetting('company_short_name', 'TSACI')); ?></title>
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

        /* Product / application cards */
        .product-card {
            transition: transform 0.35s cubic-bezier(0.4, 0, 0.2, 1), border-color 0.35s ease, box-shadow 0.35s ease;
        }
        .product-card:hover {
            transform: translateY(-6px);
            border-color: #3d7a66;
            box-shadow: 0 12px 32px -12px rgba(35, 51, 44, 0.15);
        }
        .product-card:hover .product-icon {
            transform: scale(1.08) rotate(-3deg);
        }

        .product-icon {
            background: linear-gradient(135deg, #3d7a66, #60796e);
            transition: transform 0.35s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .application-icon {
            background: linear-gradient(135deg, #3d7a66, #60796e);
            transition: transform 0.35s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .app-card:hover .application-icon {
            transform: scale(1.08);
        }

        /* Spec stat cards */
        .spec-card {
            transition: transform 0.35s cubic-bezier(0.4, 0, 0.2, 1), border-color 0.35s ease;
        }
        .spec-card:hover {
            transform: translateY(-4px);
            border-color: #3d7a66;
        }

        /* Tab navigation pills */
        .nav-pill {
            transition: all 0.3s ease;
        }
        .nav-pill.active {
            background-color: #23332c;
            color: white;
            border-color: #23332c;
        }
        .nav-pill:hover:not(.active) {
            background-color: #eaf0ec;
            border-color: #3d7a66;
            color: #23332c;
        }

        /* Specs table */
        .specs-table {
            border-collapse: separate;
            border-spacing: 0;
        }
        .specs-table th {
            background-color: #23332c;
            color: white;
        }
        .specs-table tbody tr {
            transition: background-color 0.2s ease;
        }
        .specs-table tbody tr:hover {
            background-color: #f7faf8;
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
                    <i class="fas fa-cube text-xs"></i> Product Catalog
                </span>
                <h1 class="text-4xl lg:text-6xl font-bold mb-5 leading-tight mt-4 fade-in fade-in-delay-2"><?php echo htmlspecialchars_safe($page_header_title); ?></h1>
                <p class="text-lg lg:text-xl text-white/85 max-w-2xl mx-auto fade-in fade-in-delay-3"><?php echo htmlspecialchars_safe($page_header_subtitle); ?></p>
            </div>
        </div>
    </section>

    <!-- Product Categories -->
    <section id="main" class="py-20 lg:py-24 relative overflow-hidden">
        <!-- Subtle dot grid backdrop -->
        <div class="absolute inset-0 dot-grid opacity-40"></div>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative">
            <div class="text-center mb-12 reveal">
                <span class="eyebrow mb-4">
                    <i class="fas fa-cubes text-xs"></i> Catalog
                </span>
                <h2 class="text-3xl lg:text-4xl font-bold text-[#23332c] mb-4 mt-4"><?php echo htmlspecialchars_safe($section_title); ?></h2>
                <p class="text-lg text-[#7d8b84] max-w-2xl mx-auto"><?php echo htmlspecialchars_safe($section_subtitle); ?></p>
            </div>
            
            <!-- Navigation Pills -->
            <div class="flex flex-wrap justify-center gap-3 mb-12 reveal">
                <button class="nav-pill active border-2 border-[#d6ded9] text-[#23332c] px-6 py-3 rounded-full font-medium text-sm" onclick="showTab('granulated', this)">
                    <i class="fas fa-cubes mr-2"></i>Granulated Activated Carbon
                </button>
                <button class="nav-pill border-2 border-[#d6ded9] text-[#23332c] px-6 py-3 rounded-full font-medium text-sm" onclick="showTab('husk', this)">
                    <i class="fas fa-seedling mr-2"></i>Coconut Husk Products
                </button>
                <button class="nav-pill border-2 border-[#d6ded9] text-[#23332c] px-6 py-3 rounded-full font-medium text-sm" onclick="showTab('custom', this)">
                    <i class="fas fa-cogs mr-2"></i>Custom Formulations
                </button>
            </div>

            <!-- Tab Content -->
            <div id="tabContent">
                <!-- Granulated Activated Carbon (PRIMARY) -->
                <div id="granulated" class="tab-pane">
                    <!-- Product Highlight -->
                    <div class="bg-[#23332c] text-white rounded-2xl p-8 lg:p-10 mb-12 text-center relative overflow-hidden reveal">
                        <div class="orb orb-1" style="background: #3d7a66; opacity: 0.25;"></div>
                        <div class="relative">
                            <span class="eyebrow bg-white/15 text-white/90 mb-4">
                                <i class="fas fa-award text-xs"></i> Flagship Product
                            </span>
                            <h3 class="text-3xl lg:text-4xl font-bold mb-4 mt-3"><?php echo htmlspecialchars_safe($gac_product ? $gac_product['name'] : 'Premium Granulated Activated Carbon (GAC)'); ?></h3>
                            <?php if ($gac_product && !empty($gac_product['specifications'])): ?>
                                <?php 
                                // Extract first specification as subtitle if available
                                $specs = explode('•', $gac_product['specifications']);
                                $first_spec = trim($specs[0] ?? '');
                                ?>
                                <?php if ($first_spec): ?>
                                    <p class="text-2xl font-bold mb-2 text-[#8bc34a]"><?php echo htmlspecialchars_safe($first_spec); ?></p>
                                <?php endif; ?>
                            <?php else: ?>
                                <p class="text-2xl font-bold mb-2 text-[#8bc34a]">2mm below (granulated) - Strict Quality Specification</p>
                            <?php endif; ?>
                            <p class="text-lg text-white/80">Optimized for Municipal Water Treatment Facilities</p>
                        </div>
                    </div>

                    <!-- Key Specifications -->
                    <?php if ($gac_product && !empty($gac_product['specifications'])): ?>
                        <?php 
                        // Parse specifications (format: "spec1 • spec2 • spec3")
                        $specs = array_map('trim', explode('•', $gac_product['specifications']));
                        $specs = array_filter($specs); // Remove empty items
                        $spec_count = count($specs);
                        $grid_cols = $spec_count <= 2 ? 'md:grid-cols-2' : ($spec_count <= 3 ? 'md:grid-cols-3' : 'md:grid-cols-2 lg:grid-cols-4');
                        ?>
                        <div class="grid grid-cols-1 <?php echo $grid_cols; ?> gap-6 mb-12">
                            <?php foreach ($specs as $index => $spec): ?>
                                <div class="spec-card bg-white border border-[#e6ece8] rounded-2xl p-6 text-center reveal <?php echo 'reveal-delay-' . ((($index % 4) + 1)); ?>">
                                    <?php 
                                    // Try to extract value and label from spec (e.g., "2mm Particle Size")
                                    $parts = preg_split('/\s+/', trim($spec), 2);
                                    $value = $parts[0] ?? $spec;
                                    $label = $parts[1] ?? 'Specification';
                                    ?>
                                    <div class="text-4xl font-bold text-[#23332c] mb-2"><?php echo htmlspecialchars_safe($value); ?></div>
                                    <div class="text-[#7d8b84] text-sm"><?php echo htmlspecialchars_safe($label); ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <!-- Default Specifications -->
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-12">
                            <div class="spec-card bg-white border border-[#e6ece8] rounded-2xl p-6 text-center reveal reveal-delay-1">
                                <div class="text-4xl font-bold text-[#23332c] mb-2">2mm</div>
                                <div class="text-[#7d8b84] text-sm">Particle Size</div>
                                <div class="text-xs text-[#8a978f] mt-1">Below (granulated)</div>
                            </div>
                            <div class="spec-card bg-white border border-[#e6ece8] rounded-2xl p-6 text-center reveal reveal-delay-2">
                                <div class="text-4xl font-bold text-[#23332c] mb-2">GAC</div>
                                <div class="text-[#7d8b84] text-sm">Type</div>
                                <div class="text-xs text-[#8a978f] mt-1">Granular Activated Carbon</div>
                            </div>
                            <div class="spec-card bg-white border border-[#e6ece8] rounded-2xl p-6 text-center reveal reveal-delay-3">
                                <div class="text-4xl font-bold text-[#23332c] mb-2">200+</div>
                                <div class="text-[#7d8b84] text-sm">Municipal Clients</div>
                                <div class="text-xs text-[#8a978f] mt-1">Trusted by facilities</div>
                            </div>
                            <div class="spec-card bg-white border border-[#e6ece8] rounded-2xl p-6 text-center reveal reveal-delay-4">
                                <div class="text-4xl font-bold text-[#23332c] mb-2">25+</div>
                                <div class="text-[#7d8b84] text-sm">Years Experience</div>
                                <div class="text-xs text-[#8a978f] mt-1">Proven track record</div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Product Description -->
                    <div class="bg-white border border-[#e6ece8] rounded-2xl p-8 mb-8 reveal">
                        <h3 class="text-2xl font-semibold text-[#23332c] mb-4">Product Overview</h3>
                        <?php if ($gac_product && !empty($gac_product['description'])): ?>
                            <div class="text-[#5a6b62] mb-4 prose prose-lg max-w-none leading-relaxed">
                                <?php echo htmlspecialchars_decode($gac_product['description'], ENT_QUOTES); ?>
                            </div>
                        <?php else: ?>
                            <p class="text-[#5a6b62] mb-4 leading-relaxed">Our premium Granulated Activated Carbon (GAC) is manufactured with strict quality control to meet the <strong>2mm below (granulated)</strong> particle size specification. This product is specifically optimized for municipal water treatment facilities, providing exceptional performance in drinking water purification, contaminant removal, and taste/odor control.</p>
                            <p class="text-[#5a6b62] mb-4 leading-relaxed">With over 25 years of experience serving 200+ municipal water treatment facilities, our 2mm granulated activated carbon has proven reliability and consistent quality that meets or exceeds industry standards.</p>
                        <?php endif; ?>
                        
                        <!-- Key Features -->
                        <?php if ($gac_product && !empty($gac_product['features'])): ?>
                            <div class="mt-6">
                                <h4 class="text-lg font-semibold text-[#23332c] mb-4">Key Features</h4>
                                <ul class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                    <?php 
                                    $features = array_map('trim', explode('•', $gac_product['features']));
                                    $features = array_filter($features);
                                    foreach ($features as $feature): 
                                    ?>
                                        <li class="flex items-center text-[#5a6b62]"><i class="fas fa-check text-[#3d7a66] mr-3"></i><?php echo htmlspecialchars_safe($feature); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                        
                        <div class="flex flex-col sm:flex-row gap-3 mt-8">
                            <a href="resources.php" class="bg-[#23332c] hover:bg-[#3a4a41] text-white font-medium py-3 px-7 rounded-full transition-colors text-center inline-flex items-center justify-center gap-2">
                                <i class="fas fa-download text-xs"></i>Download Technical Data Sheet
                            </a>
                            <a href="contact.php" class="border border-[#d6ded9] text-[#23332c] hover:bg-[#eff4f1] font-medium py-3 px-7 rounded-full transition-colors text-center inline-flex items-center justify-center gap-2">
                                <i class="fas fa-quote-left text-xs"></i>Request Quote
                            </a>
                        </div>
                    </div>

                    <!-- Primary Applications -->
                    <div class="mb-12">
                        <h3 class="text-2xl font-semibold text-[#23332c] mb-8 text-center reveal">Primary Applications</h3>
                        <?php if ($gac_product && !empty($gac_product['applications'])): ?>
                            <?php 
                            $applications = array_map('trim', explode('•', $gac_product['applications']));
                            $applications = array_filter($applications);
                            $app_count = count($applications);
                            $grid_cols = $app_count <= 2 ? 'md:grid-cols-2' : ($app_count <= 3 ? 'md:grid-cols-3' : 'md:grid-cols-2 lg:grid-cols-3');
                            ?>
                            <div class="grid grid-cols-1 <?php echo $grid_cols; ?> gap-6">
                                <?php foreach ($applications as $index => $application): ?>
                                    <div class="product-card bg-white border border-[#e6ece8] rounded-2xl p-8 h-full reveal <?php echo 'reveal-delay-' . ((($index % 3) + 1)); ?>">
                                        <div class="text-center">
                                            <div class="product-icon w-20 h-20 rounded-2xl flex items-center justify-center mx-auto mb-6">
                                                <?php 
                                                $icon = 'fas fa-industry';
                                                if (stripos($application, 'municipal') !== false || stripos($application, 'water') !== false) {
                                                    $icon = 'fas fa-building';
                                                } elseif (stripos($application, 'air') !== false || stripos($application, 'purification') !== false) {
                                                    $icon = 'fas fa-wind';
                                                } elseif (stripos($application, 'wastewater') !== false) {
                                                    $icon = 'fas fa-tint';
                                                }
                                                ?>
                                                <i class="<?php echo $icon; ?> text-3xl text-white"></i>
                                            </div>
                                            <h4 class="text-xl font-semibold text-[#23332c] mb-3"><?php echo htmlspecialchars_safe(ucwords($application)); ?></h4>
                                            <?php if ($index === 0 && stripos($application, 'municipal') !== false): ?>
                                                <span class="eyebrow mb-4">Primary Application</span>
                                            <?php endif; ?>
                                            <a href="contact.php" class="inline-flex items-center gap-2 border border-[#d6ded9] text-[#23332c] hover:bg-[#23332c] hover:text-white hover:border-[#23332c] font-medium py-2.5 px-5 rounded-full transition-colors text-sm mt-4">Learn More <i class="fas fa-arrow-right text-xs"></i></a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                        <!-- Default Applications -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div class="product-card bg-white border border-[#e6ece8] rounded-2xl p-8 h-full reveal reveal-delay-1">
                                <div class="text-center">
                                    <div class="product-icon w-20 h-20 rounded-2xl flex items-center justify-center mx-auto mb-6">
                                        <i class="fas fa-building text-3xl text-white"></i>
                                    </div>
                                    <h4 class="text-xl font-semibold text-[#23332c] mb-3">Municipal Water Treatment</h4>
                                    <span class="eyebrow mb-4">Primary Application</span>
                                    <p class="text-[#7d8b84] mb-6 leading-relaxed text-sm">Our 2mm granulated activated carbon is the preferred choice for municipal water treatment facilities. Proven track record with 200+ municipal clients nationwide.</p>
                                    <ul class="text-left space-y-2 mb-6">
                                        <li class="flex items-center text-[#5a6b62] text-sm"><i class="fas fa-check text-[#3d7a66] mr-3"></i>Drinking water purification</li>
                                        <li class="flex items-center text-[#5a6b62] text-sm"><i class="fas fa-check text-[#3d7a66] mr-3"></i>Chlorine and chloramine removal</li>
                                        <li class="flex items-center text-[#5a6b62] text-sm"><i class="fas fa-check text-[#3d7a66] mr-3"></i>Taste and odor control</li>
                                        <li class="flex items-center text-[#5a6b62] text-sm"><i class="fas fa-check text-[#3d7a66] mr-3"></i>NSF/ANSI certified</li>
                                        <li class="flex items-center text-[#5a6b62] text-sm"><i class="fas fa-check text-[#3d7a66] mr-3"></i>Regulatory compliance</li>
                                    </ul>
                                    <a href="case-studies.php" class="inline-flex items-center gap-2 border border-[#d6ded9] text-[#23332c] hover:bg-[#23332c] hover:text-white hover:border-[#23332c] font-medium py-2.5 px-5 rounded-full transition-colors text-sm">View Case Studies <i class="fas fa-arrow-right text-xs"></i></a>
                                </div>
                            </div>
                            <div class="product-card bg-white border border-[#e6ece8] rounded-2xl p-8 h-full reveal reveal-delay-2">
                                <div class="text-center">
                                    <div class="product-icon w-20 h-20 rounded-2xl flex items-center justify-center mx-auto mb-6">
                                        <i class="fas fa-industry text-3xl text-white"></i>
                                    </div>
                                    <h4 class="text-xl font-semibold text-[#23332c] mb-3">Industrial Wastewater Treatment</h4>
                                    <p class="text-[#7d8b84] mb-6 leading-relaxed text-sm">High-performance 2mm granulated activated carbon for industrial wastewater treatment and process water purification.</p>
                                    <ul class="text-left space-y-2">
                                        <li class="flex items-center text-[#5a6b62] text-sm"><i class="fas fa-check text-[#3d7a66] mr-3"></i>Organic contaminant removal</li>
                                        <li class="flex items-center text-[#5a6b62] text-sm"><i class="fas fa-check text-[#3d7a66] mr-3"></i>High adsorption capacity</li>
                                        <li class="flex items-center text-[#5a6b62] text-sm"><i class="fas fa-check text-[#3d7a66] mr-3"></i>Regenerable options</li>
                                    </ul>
                                </div>
                            </div>
                            <div class="product-card bg-white border border-[#e6ece8] rounded-2xl p-8 h-full reveal reveal-delay-3">
                                <div class="text-center">
                                    <div class="product-icon w-20 h-20 rounded-2xl flex items-center justify-center mx-auto mb-6">
                                        <i class="fas fa-wind text-3xl text-white"></i>
                                    </div>
                                    <h4 class="text-xl font-semibold text-[#23332c] mb-3">Air Purification</h4>
                                    <p class="text-[#7d8b84] mb-6 leading-relaxed text-sm">Versatile 2mm granulated activated carbon for industrial air filtration and VOC removal systems.</p>
                                    <ul class="text-left space-y-2">
                                        <li class="flex items-center text-[#5a6b62] text-sm"><i class="fas fa-check text-[#3d7a66] mr-3"></i>VOC and odor removal</li>
                                        <li class="flex items-center text-[#5a6b62] text-sm"><i class="fas fa-check text-[#3d7a66] mr-3"></i>High surface area</li>
                                        <li class="flex items-center text-[#5a6b62] text-sm"><i class="fas fa-check text-[#3d7a66] mr-3"></i>Customizable configurations</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Coconut Husk Products (NEW) -->
                <div id="husk" class="tab-pane hidden">
                    <div class="bg-[#3d7a66] text-white rounded-2xl p-8 lg:p-10 mb-12 text-center relative overflow-hidden reveal">
                        <div class="orb orb-1" style="background: #8bc34a; opacity: 0.25;"></div>
                        <div class="relative">
                            <span class="eyebrow bg-white/15 text-white/90 mb-4">
                                <i class="fas fa-seedling text-xs"></i> Sustainable
                            </span>
                            <h3 class="text-3xl lg:text-4xl font-bold mb-4 mt-3">Coconut Husk Products</h3>
                            <p class="text-lg text-white/85">Sustainable Growing Mediums from Our Zero-Waste Operations</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-12">
                        <!-- Coconut Husk Chips -->
                        <div class="product-card bg-white border border-[#e6ece8] rounded-2xl p-8 h-full reveal reveal-delay-1">
                            <div class="text-center">
                                <div class="product-icon w-20 h-20 rounded-2xl flex items-center justify-center mx-auto mb-6">
                                    <i class="fas fa-seedling text-3xl text-white"></i>
                                </div>
                                <h4 class="text-2xl font-semibold text-[#23332c] mb-2">Coconut Husk Chips</h4>
                                <span class="eyebrow mb-4">Premium Growing Medium</span>
                                <p class="text-[#7d8b84] mb-6 leading-relaxed text-sm">High-quality coconut husk chips processed from our zero-waste operations. An excellent sustainable alternative to traditional growing mediums, providing superior water retention and aeration for horticultural applications.</p>
                                
                                <h5 class="text-lg font-semibold text-[#23332c] mb-3 text-left">Key Features:</h5>
                                <ul class="text-left space-y-2 mb-6">
                                    <li class="flex items-center text-[#5a6b62] text-sm"><i class="fas fa-check text-[#3d7a66] mr-3"></i>Excellent water retention capacity</li>
                                    <li class="flex items-center text-[#5a6b62] text-sm"><i class="fas fa-check text-[#3d7a66] mr-3"></i>Superior aeration properties</li>
                                    <li class="flex items-center text-[#5a6b62] text-sm"><i class="fas fa-check text-[#3d7a66] mr-3"></i>Sustainable and eco-friendly</li>
                                    <li class="flex items-center text-[#5a6b62] text-sm"><i class="fas fa-check text-[#3d7a66] mr-3"></i>Natural fiber composition</li>
                                    <li class="flex items-center text-[#5a6b62] text-sm"><i class="fas fa-check text-[#3d7a66] mr-3"></i>pH balanced and disease-free</li>
                                    <li class="flex items-center text-[#5a6b62] text-sm"><i class="fas fa-check text-[#3d7a66] mr-3"></i>Consistent particle size</li>
                                </ul>

                                <h5 class="text-lg font-semibold text-[#23332c] mb-3 text-left">Applications:</h5>
                                <ul class="text-left space-y-2 mb-6">
                                    <li class="flex items-center text-[#5a6b62] text-sm"><i class="fas fa-seedling text-[#3d7a66] mr-3"></i>Horticulture and greenhouse operations</li>
                                    <li class="flex items-center text-[#5a6b62] text-sm"><i class="fas fa-seedling text-[#3d7a66] mr-3"></i>Commercial agriculture</li>
                                    <li class="flex items-center text-[#5a6b62] text-sm"><i class="fas fa-seedling text-[#3d7a66] mr-3"></i>Professional gardening</li>
                                    <li class="flex items-center text-[#5a6b62] text-sm"><i class="fas fa-seedling text-[#3d7a66] mr-3"></i>Container growing</li>
                                </ul>

                                <div class="flex flex-col gap-3 mt-6">
                                    <a href="contact.php" class="bg-[#23332c] hover:bg-[#3a4a41] text-white font-medium py-3 px-7 rounded-full transition-colors text-center inline-flex items-center justify-center gap-2">
                                        <i class="fas fa-quote-left text-xs"></i>Request Quote
                                    </a>
                                    <a href="resources.php" class="border border-[#d6ded9] text-[#23332c] hover:bg-[#eff4f1] font-medium py-2.5 px-6 rounded-full transition-colors text-center inline-flex items-center justify-center gap-2 text-sm">
                                        <i class="fas fa-download text-xs"></i>Download Specifications
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Coconut Pit -->
                        <div class="product-card bg-white border border-[#e6ece8] rounded-2xl p-8 h-full reveal reveal-delay-2">
                            <div class="text-center">
                                <div class="product-icon w-20 h-20 rounded-2xl flex items-center justify-center mx-auto mb-6">
                                    <i class="fas fa-leaf text-3xl text-white"></i>
                                </div>
                                <h4 class="text-2xl font-semibold text-[#23332c] mb-2">Coconut Pit</h4>
                                <span class="eyebrow mb-4">Premium Horticultural Medium</span>
                                <p class="text-[#7d8b84] mb-6 leading-relaxed text-sm">Premium-grade coconut pit processed specifically for horticultural applications. This specialized growing medium offers exceptional performance for professional growers, greenhouse operations, and commercial agriculture.</p>
                                
                                <h5 class="text-lg font-semibold text-[#23332c] mb-3 text-left">Key Features:</h5>
                                <ul class="text-left space-y-2 mb-6">
                                    <li class="flex items-center text-[#5a6b62] text-sm"><i class="fas fa-check text-[#3d7a66] mr-3"></i>Premium quality for professional use</li>
                                    <li class="flex items-center text-[#5a6b62] text-sm"><i class="fas fa-check text-[#3d7a66] mr-3"></i>Optimized particle size distribution</li>
                                    <li class="flex items-center text-[#5a6b62] text-sm"><i class="fas fa-check text-[#3d7a66] mr-3"></i>Superior root development support</li>
                                    <li class="flex items-center text-[#5a6b62] text-sm"><i class="fas fa-check text-[#3d7a66] mr-3"></i>Enhanced nutrient retention</li>
                                    <li class="flex items-center text-[#5a6b62] text-sm"><i class="fas fa-check text-[#3d7a66] mr-3"></i>Long-lasting performance</li>
                                    <li class="flex items-center text-[#5a6b62] text-sm"><i class="fas fa-check text-[#3d7a66] mr-3"></i>Zero-waste sustainable source</li>
                                </ul>

                                <h5 class="text-lg font-semibold text-[#23332c] mb-3 text-left">Applications:</h5>
                                <ul class="text-left space-y-2 mb-6">
                                    <li class="flex items-center text-[#5a6b62] text-sm"><i class="fas fa-seedling text-[#3d7a66] mr-3"></i>Professional horticulture</li>
                                    <li class="flex items-center text-[#5a6b62] text-sm"><i class="fas fa-seedling text-[#3d7a66] mr-3"></i>Greenhouse cultivation</li>
                                    <li class="flex items-center text-[#5a6b62] text-sm"><i class="fas fa-seedling text-[#3d7a66] mr-3"></i>Commercial growing operations</li>
                                    <li class="flex items-center text-[#5a6b62] text-sm"><i class="fas fa-seedling text-[#3d7a66] mr-3"></i>Specialized crop production</li>
                                </ul>

                                <div class="flex flex-col gap-3 mt-6">
                                    <a href="contact.php" class="bg-[#23332c] hover:bg-[#3a4a41] text-white font-medium py-3 px-7 rounded-full transition-colors text-center inline-flex items-center justify-center gap-2">
                                        <i class="fas fa-quote-left text-xs"></i>Request Quote
                                    </a>
                                    <a href="resources.php" class="border border-[#d6ded9] text-[#23332c] hover:bg-[#eff4f1] font-medium py-2.5 px-6 rounded-full transition-colors text-center inline-flex items-center justify-center gap-2 text-sm">
                                        <i class="fas fa-download text-xs"></i>Download Specifications
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Zero-Waste Message -->
                    <div class="bg-[#eef3f0] border border-[#e6ece8] border-l-4 border-l-[#3d7a66] rounded-2xl p-6 reveal">
                        <div class="flex items-start">
                            <div class="w-12 h-12 rounded-full bg-[#3d7a66] flex items-center justify-center flex-shrink-0 mr-4">
                                <i class="fas fa-recycle text-xl text-white"></i>
                            </div>
                            <div>
                                <h4 class="text-lg font-semibold text-[#23332c] mb-2">Sustainable Zero-Waste Operations</h4>
                                <p class="text-[#5a6b62] leading-relaxed text-sm">Our Coconut Husk Products are part of our integrated zero-waste operations. By utilizing all parts of the coconut, we maximize resource efficiency while providing high-quality growing mediums for the horticultural industry. These products represent our commitment to sustainability and environmental responsibility.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Custom Formulations -->
                <div id="custom" class="tab-pane hidden">
                    <div class="bg-[#60796e] text-white rounded-2xl p-8 lg:p-10 mb-12 text-center relative overflow-hidden reveal">
                        <div class="orb orb-1" style="background: #8bc34a; opacity: 0.25;"></div>
                        <div class="relative">
                            <span class="eyebrow bg-white/15 text-white/90 mb-4">
                                <i class="fas fa-cogs text-xs"></i> Tailored Solutions
                            </span>
                            <h3 class="text-3xl lg:text-4xl font-bold mb-4 mt-3">Custom Formulations</h3>
                            <p class="text-lg text-white/85">Tailored activated carbon solutions for specialized applications</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="product-card bg-white border border-[#e6ece8] rounded-2xl p-8 h-full reveal reveal-delay-1">
                            <div class="text-center">
                                <div class="product-icon w-20 h-20 rounded-2xl flex items-center justify-center mx-auto mb-6">
                                    <i class="fas fa-cogs text-3xl text-white"></i>
                                </div>
                                <h4 class="text-xl font-semibold text-[#23332c] mb-3">Custom Particle Sizes</h4>
                                <p class="text-[#7d8b84] mb-6 leading-relaxed text-sm">Tailored activated carbon formulations with custom particle sizes designed for your specific application requirements beyond standard 2mm granulated specification.</p>
                                <ul class="text-left space-y-2 mb-6">
                                    <li class="flex items-center text-[#5a6b62] text-sm"><i class="fas fa-check text-[#3d7a66] mr-3"></i>Custom particle size distributions</li>
                                    <li class="flex items-center text-[#5a6b62] text-sm"><i class="fas fa-check text-[#3d7a66] mr-3"></i>Specialized surface chemistry</li>
                                    <li class="flex items-center text-[#5a6b62] text-sm"><i class="fas fa-check text-[#3d7a66] mr-3"></i>Application-specific testing</li>
                                    <li class="flex items-center text-[#5a6b62] text-sm"><i class="fas fa-check text-[#3d7a66] mr-3"></i>Performance optimization</li>
                                </ul>
                                <a href="contact.php" class="inline-flex items-center gap-2 border border-[#d6ded9] text-[#23332c] hover:bg-[#23332c] hover:text-white hover:border-[#23332c] font-medium py-2.5 px-5 rounded-full transition-colors text-sm">Request Consultation <i class="fas fa-arrow-right text-xs"></i></a>
                            </div>
                        </div>
                        <div class="product-card bg-white border border-[#e6ece8] rounded-2xl p-8 h-full reveal reveal-delay-2">
                            <div class="text-center">
                                <div class="product-icon w-20 h-20 rounded-2xl flex items-center justify-center mx-auto mb-6">
                                    <i class="fas fa-tools text-3xl text-white"></i>
                                </div>
                                <h4 class="text-xl font-semibold text-[#23332c] mb-3">System Integration</h4>
                                <p class="text-[#7d8b84] mb-6 leading-relaxed text-sm">Complete activated carbon system design and integration for complex applications.</p>
                                <ul class="text-left space-y-2 mb-6">
                                    <li class="flex items-center text-[#5a6b62] text-sm"><i class="fas fa-check text-[#3d7a66] mr-3"></i>System design</li>
                                    <li class="flex items-center text-[#5a6b62] text-sm"><i class="fas fa-check text-[#3d7a66] mr-3"></i>Installation support</li>
                                    <li class="flex items-center text-[#5a6b62] text-sm"><i class="fas fa-check text-[#3d7a66] mr-3"></i>Performance monitoring</li>
                                </ul>
                                <a href="contact.php" class="inline-flex items-center gap-2 border border-[#d6ded9] text-[#23332c] hover:bg-[#23332c] hover:text-white hover:border-[#23332c] font-medium py-2.5 px-5 rounded-full transition-colors text-sm">Request Consultation <i class="fas fa-arrow-right text-xs"></i></a>
                            </div>
                        </div>
                        <div class="product-card bg-white border border-[#e6ece8] rounded-2xl p-8 h-full reveal reveal-delay-3">
                            <div class="text-center">
                                <div class="product-icon w-20 h-20 rounded-2xl flex items-center justify-center mx-auto mb-6">
                                    <i class="fas fa-microscope text-3xl text-white"></i>
                                </div>
                                <h4 class="text-xl font-semibold text-[#23332c] mb-3">R&D Support</h4>
                                <p class="text-[#7d8b84] mb-6 leading-relaxed text-sm">Research and development support for new activated carbon applications and technologies.</p>
                                <ul class="text-left space-y-2 mb-6">
                                    <li class="flex items-center text-[#5a6b62] text-sm"><i class="fas fa-check text-[#3d7a66] mr-3"></i>Laboratory testing</li>
                                    <li class="flex items-center text-[#5a6b62] text-sm"><i class="fas fa-check text-[#3d7a66] mr-3"></i>Performance analysis</li>
                                    <li class="flex items-center text-[#5a6b62] text-sm"><i class="fas fa-check text-[#3d7a66] mr-3"></i>Technical consultation</li>
                                </ul>
                                <a href="contact.php" class="inline-flex items-center gap-2 border border-[#d6ded9] text-[#23332c] hover:bg-[#23332c] hover:text-white hover:border-[#23332c] font-medium py-2.5 px-5 rounded-full transition-colors text-sm">Request Consultation <i class="fas fa-arrow-right text-xs"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Product Specifications -->
    <section id="specifications" class="bg-[#f5f7f5] py-20 lg:py-24">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16 reveal">
                <span class="eyebrow mb-4">
                    <i class="fas fa-table text-xs"></i> Technical Specs
                </span>
                <h2 class="text-3xl lg:text-4xl font-bold text-[#23332c] mb-4 mt-4"><?php echo htmlspecialchars_safe($specifications_title); ?></h2>
                <p class="text-lg text-[#7d8b84] mb-4"><?php echo htmlspecialchars_safe($specifications_subtitle); ?></p>
                <p class="text-base font-medium text-[#3d7a66]">Particle Size: <strong>2mm below (granulated)</strong> - Strict Quality Control</p>
            </div>
            <div class="max-w-6xl mx-auto reveal">
                <div class="bg-white border border-[#e6ece8] rounded-2xl overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[600px] specs-table">
                        <thead>
                            <tr>
                                <th class="px-6 py-4 text-left font-semibold">Property</th>
                                <th class="px-6 py-4 text-center font-semibold">Standard Grade</th>
                                <th class="px-6 py-4 text-center font-semibold">Premium Grade</th>
                                <th class="px-6 py-4 text-center font-semibold">Ultra-Pure Grade</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#e6ece8]">
                            <tr>
                                <td class="px-6 py-4 font-medium text-[#23332c]">Surface Area (m²/g)</td>
                                <td class="px-6 py-4 text-center text-[#5a6b62]">800-1000</td>
                                <td class="px-6 py-4 text-center text-[#5a6b62]">1000-1200</td>
                                <td class="px-6 py-4 text-center text-[#5a6b62]">1200-1500</td>
                            </tr>
                            <tr>
                                <td class="px-6 py-4 font-medium text-[#23332c]">Iodine Number</td>
                                <td class="px-6 py-4 text-center text-[#5a6b62]">800-1000</td>
                                <td class="px-6 py-4 text-center text-[#5a6b62]">1000-1200</td>
                                <td class="px-6 py-4 text-center text-[#5a6b62]">1200-1400</td>
                            </tr>
                            <tr>
                                <td class="px-6 py-4 font-medium text-[#23332c]">Ash Content (%)</td>
                                <td class="px-6 py-4 text-center text-[#5a6b62]">≤ 8</td>
                                <td class="px-6 py-4 text-center text-[#5a6b62]">≤ 5</td>
                                <td class="px-6 py-4 text-center text-[#5a6b62]">≤ 2</td>
                            </tr>
                            <tr>
                                <td class="px-6 py-4 font-medium text-[#23332c]">Moisture (%)</td>
                                <td class="px-6 py-4 text-center text-[#5a6b62]">≤ 5</td>
                                <td class="px-6 py-4 text-center text-[#5a6b62]">≤ 3</td>
                                <td class="px-6 py-4 text-center text-[#5a6b62]">≤ 1</td>
                            </tr>
                            <tr class="bg-[#eef3f0]">
                                <td class="px-6 py-4 font-semibold text-[#23332c]">Particle Size</td>
                                <td class="px-6 py-4 text-center font-bold text-[#3d7a66]">2mm below (granulated)</td>
                                <td class="px-6 py-4 text-center font-bold text-[#3d7a66]">2mm below (granulated)</td>
                                <td class="px-6 py-4 text-center font-bold text-[#3d7a66]">2mm below (granulated)</td>
                            </tr>
                            <tr>
                                <td class="px-6 py-4 font-medium text-[#23332c]">Bulk Density (g/L)</td>
                                <td class="px-6 py-4 text-center text-[#5a6b62]">400-500</td>
                                <td class="px-6 py-4 text-center text-[#5a6b62]">450-550</td>
                                <td class="px-6 py-4 text-center text-[#5a6b62]">500-600</td>
                            </tr>
                        </tbody>
                    </table>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Applications -->
    <section id="applications" class="py-20 lg:py-24">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16 reveal">
                <span class="eyebrow mb-4">
                    <i class="fas fa-th-large text-xs"></i> Industries
                </span>
                <h2 class="text-3xl lg:text-4xl font-bold text-[#23332c] mb-4 mt-4"><?php echo htmlspecialchars_safe($applications_title); ?></h2>
                <p class="text-lg text-[#7d8b84] max-w-2xl mx-auto"><?php echo nl2br_safe($applications_subtitle); ?></p>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-5">
                <div class="app-card bg-[#23332c] text-white rounded-2xl p-6 text-center reveal reveal-delay-1 relative overflow-hidden">
                    <div class="orb orb-1" style="background: #3d7a66; opacity: 0.25;"></div>
                    <div class="relative">
                        <div class="application-icon w-14 h-14 rounded-2xl flex items-center justify-center mx-auto mb-4 bg-white">
                            <i class="fas fa-building text-2xl text-[#3d7a66]"></i>
                        </div>
                        <h5 class="text-base font-semibold mb-2">Municipal Water Treatment</h5>
                        <span class="inline-block text-xs font-semibold bg-[#8bc34a] text-[#23332c] px-2.5 py-1 rounded-full mb-2">PRIMARY APPLICATION</span>
                        <p class="text-white/70 text-sm">Drinking water treatment for cities and towns - 200+ facilities served</p>
                    </div>
                </div>
                <div class="app-card bg-white border border-[#e6ece8] rounded-2xl p-6 text-center reveal reveal-delay-2">
                    <div class="application-icon w-14 h-14 rounded-2xl flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-industry text-2xl text-white"></i>
                    </div>
                    <h5 class="text-base font-semibold text-[#23332c] mb-2">Industrial Wastewater</h5>
                    <p class="text-[#7d8b84] text-sm">Treatment of industrial process water</p>
                </div>
                <div class="app-card bg-white border border-[#e6ece8] rounded-2xl p-6 text-center reveal reveal-delay-3">
                    <div class="application-icon w-14 h-14 rounded-2xl flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-hospital text-2xl text-white"></i>
                    </div>
                    <h5 class="text-base font-semibold text-[#23332c] mb-2">Healthcare</h5>
                    <p class="text-[#7d8b84] text-sm">Medical air filtration and sterilization</p>
                </div>
                <div class="app-card bg-white border border-[#e6ece8] rounded-2xl p-6 text-center reveal reveal-delay-4">
                    <div class="application-icon w-14 h-14 rounded-2xl flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-flask text-2xl text-white"></i>
                    </div>
                    <h5 class="text-base font-semibold text-[#23332c] mb-2">Pharmaceutical</h5>
                    <p class="text-[#7d8b84] text-sm">Drug purification and manufacturing</p>
                </div>
                <div class="app-card bg-white border border-[#e6ece8] rounded-2xl p-6 text-center reveal reveal-delay-1">
                    <div class="application-icon w-14 h-14 rounded-2xl flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-gas-pump text-2xl text-white"></i>
                    </div>
                    <h5 class="text-base font-semibold text-[#23332c] mb-2">Oil & Gas</h5>
                    <p class="text-[#7d8b84] text-sm">Fuel purification and gas treatment</p>
                </div>
                <div class="app-card bg-white border border-[#e6ece8] rounded-2xl p-6 text-center reveal reveal-delay-2">
                    <div class="application-icon w-14 h-14 rounded-2xl flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-home text-2xl text-white"></i>
                    </div>
                    <h5 class="text-base font-semibold text-[#23332c] mb-2">Residential</h5>
                    <p class="text-[#7d8b84] text-sm">Home water and air filtration</p>
                </div>
                <div class="app-card bg-white border border-[#e6ece8] rounded-2xl p-6 text-center reveal reveal-delay-3">
                    <div class="application-icon w-14 h-14 rounded-2xl flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-fish text-2xl text-white"></i>
                    </div>
                    <h5 class="text-base font-semibold text-[#23332c] mb-2">Aquaculture</h5>
                    <p class="text-[#7d8b84] text-sm">Fish farming and aquarium systems</p>
                </div>
                <div class="app-card bg-white border border-[#e6ece8] rounded-2xl p-6 text-center reveal reveal-delay-4">
                    <div class="application-icon w-14 h-14 rounded-2xl flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-leaf text-2xl text-white"></i>
                    </div>
                    <h5 class="text-base font-semibold text-[#23332c] mb-2">Environmental</h5>
                    <p class="text-[#7d8b84] text-sm">Pollution control and remediation</p>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section id="cta" class="relative overflow-hidden py-20 lg:py-24">
        <!-- Gradient background with orbs -->
        <div class="absolute inset-0 page-header-gradient"></div>
        <div class="orb orb-1" style="background: #8bc34a; opacity: 0.25;"></div>
        <div class="orb orb-2" style="background: #3d7a66; opacity: 0.3;"></div>
        <div class="page-header-pattern absolute inset-0 opacity-30"></div>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative text-center text-white reveal">
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
            </div>
        </div>
    </section>

    <?php include 'includes/footer.php'; ?>

    <script>
        function showTab(tabName, btn) {
            // Hide all tab panes
            const tabPanes = document.querySelectorAll('.tab-pane');
            tabPanes.forEach(pane => pane.classList.add('hidden'));
            
            // Show selected tab pane
            document.getElementById(tabName).classList.remove('hidden');

            // Re-trigger reveal animations for the newly shown tab
            const pane = document.getElementById(tabName);
            pane.querySelectorAll('.reveal').forEach(el => {
                el.classList.remove('is-visible');
                // Use rAF to ensure the class toggles apply before re-adding
                requestAnimationFrame(() => requestAnimationFrame(() => {
                    el.classList.add('is-visible');
                }));
            });
            
            // Update active state of navigation pills
            const navPills = document.querySelectorAll('.nav-pill');
            navPills.forEach(pill => pill.classList.remove('active'));
            btn.classList.add('active');
        }

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
