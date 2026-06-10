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
        
        .product-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .product-card:hover {
            transform: translateY(-5px);
        }
        
        .product-icon {
            background: linear-gradient(135deg, #8bc34a, #4a7c59);
        }
        
        .application-icon {
            background: linear-gradient(135deg, #8bc34a, #4a7c59);
        }
        
        .nav-pill {
            transition: all 0.3s ease;
        }
        
        .nav-pill.active {
            background-color: #2c5530;
            color: white;
        }
        
        .nav-pill:hover {
            background-color: #4a7c59;
            color: white;
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

    <!-- Product Categories -->
    <section class="py-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-4xl lg:text-5xl font-bold text-gray-900 mb-4"><?php echo htmlspecialchars_safe($section_title); ?></h2>
                <p class="text-xl text-gray-600"><?php echo htmlspecialchars_safe($section_subtitle); ?></p>
            </div>
            
            <!-- Navigation Pills -->
            <div class="flex flex-wrap justify-center gap-4 mb-12">
                <button class="nav-pill active border-2 border-primary text-primary px-6 py-3 rounded-full font-medium" onclick="showTab('granulated')">
                    <i class="fas fa-cubes mr-2"></i>Granulated Activated Carbon
                </button>
                <button class="nav-pill border-2 border-primary text-primary px-6 py-3 rounded-full font-medium" onclick="showTab('husk')">
                    <i class="fas fa-seedling mr-2"></i>Coconut Husk Products
                </button>
                <button class="nav-pill border-2 border-primary text-primary px-6 py-3 rounded-full font-medium" onclick="showTab('custom')">
                    <i class="fas fa-cogs mr-2"></i>Custom Formulations
                </button>
            </div>

            <!-- Tab Content -->
            <div id="tabContent">
                <!-- Granulated Activated Carbon (PRIMARY) -->
                <div id="granulated" class="tab-pane">
                    <!-- Product Highlight -->
                    <div class="bg-gradient-to-r from-primary to-secondary text-white rounded-2xl p-8 mb-12 text-center">
                        <h3 class="text-3xl lg:text-4xl font-bold mb-4"><?php echo htmlspecialchars_safe($gac_product ? $gac_product['name'] : 'Premium Granulated Activated Carbon (GAC)'); ?></h3>
                        <?php if ($gac_product && !empty($gac_product['specifications'])): ?>
                            <?php 
                            // Extract first specification as subtitle if available
                            $specs = explode('•', $gac_product['specifications']);
                            $first_spec = trim($specs[0] ?? '');
                            ?>
                            <?php if ($first_spec): ?>
                                <p class="text-2xl font-bold mb-2"><?php echo htmlspecialchars_safe($first_spec); ?></p>
                            <?php endif; ?>
                        <?php else: ?>
                            <p class="text-2xl font-bold mb-2">2mm below (granulated) - Strict Quality Specification</p>
                        <?php endif; ?>
                        <p class="text-xl text-gray-100">Optimized for Municipal Water Treatment Facilities</p>
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
                                <div class="bg-white rounded-lg shadow-lg p-6 text-center">
                                    <?php 
                                    // Try to extract value and label from spec (e.g., "2mm Particle Size")
                                    $parts = preg_split('/\s+/', trim($spec), 2);
                                    $value = $parts[0] ?? $spec;
                                    $label = $parts[1] ?? 'Specification';
                                    ?>
                                    <div class="text-4xl font-bold text-primary mb-2"><?php echo htmlspecialchars_safe($value); ?></div>
                                    <div class="text-gray-600"><?php echo htmlspecialchars_safe($label); ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <!-- Default Specifications -->
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-12">
                            <div class="bg-white rounded-lg shadow-lg p-6 text-center">
                                <div class="text-4xl font-bold text-primary mb-2">2mm</div>
                                <div class="text-gray-600">Particle Size</div>
                                <div class="text-sm text-gray-500 mt-1">Below (granulated)</div>
                            </div>
                            <div class="bg-white rounded-lg shadow-lg p-6 text-center">
                                <div class="text-4xl font-bold text-primary mb-2">GAC</div>
                                <div class="text-gray-600">Type</div>
                                <div class="text-sm text-gray-500 mt-1">Granular Activated Carbon</div>
                            </div>
                            <div class="bg-white rounded-lg shadow-lg p-6 text-center">
                                <div class="text-4xl font-bold text-primary mb-2">200+</div>
                                <div class="text-gray-600">Municipal Clients</div>
                                <div class="text-sm text-gray-500 mt-1">Trusted by facilities</div>
                            </div>
                            <div class="bg-white rounded-lg shadow-lg p-6 text-center">
                                <div class="text-4xl font-bold text-primary mb-2">25+</div>
                                <div class="text-gray-600">Years Experience</div>
                                <div class="text-sm text-gray-500 mt-1">Proven track record</div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Product Description -->
                    <div class="bg-white rounded-2xl shadow-lg p-8 mb-8">
                        <h3 class="text-2xl font-bold text-gray-900 mb-4">Product Overview</h3>
                        <?php if ($gac_product && !empty($gac_product['description'])): ?>
                            <div class="text-gray-600 mb-4 prose prose-lg max-w-none">
                                <?php echo htmlspecialchars_decode($gac_product['description'], ENT_QUOTES); ?>
                            </div>
                        <?php else: ?>
                            <p class="text-gray-600 mb-4">Our premium Granulated Activated Carbon (GAC) is manufactured with strict quality control to meet the <strong>2mm below (granulated)</strong> particle size specification. This product is specifically optimized for municipal water treatment facilities, providing exceptional performance in drinking water purification, contaminant removal, and taste/odor control.</p>
                            <p class="text-gray-600 mb-4">With over 25 years of experience serving 200+ municipal water treatment facilities, our 2mm granulated activated carbon has proven reliability and consistent quality that meets or exceeds industry standards.</p>
                        <?php endif; ?>
                        
                        <!-- Key Features -->
                        <?php if ($gac_product && !empty($gac_product['features'])): ?>
                            <div class="mt-6">
                                <h4 class="text-xl font-bold text-gray-900 mb-4">Key Features</h4>
                                <ul class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                    <?php 
                                    $features = array_map('trim', explode('•', $gac_product['features']));
                                    $features = array_filter($features);
                                    foreach ($features as $feature): 
                                    ?>
                                        <li class="flex items-center"><i class="fas fa-check text-green-500 mr-2"></i><?php echo htmlspecialchars_safe($feature); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                        
                        <div class="flex flex-col sm:flex-row gap-4 mt-6">
                            <a href="resources.php" class="bg-primary hover:bg-secondary text-white font-bold py-3 px-8 rounded-lg transition duration-300 text-center">
                                <i class="fas fa-download mr-2"></i>Download Technical Data Sheet
                            </a>
                            <a href="contact.php" class="border-2 border-primary text-primary hover:bg-primary hover:text-white font-bold py-3 px-8 rounded-lg transition duration-300 text-center">
                                <i class="fas fa-quote-left mr-2"></i>Request Quote
                            </a>
                        </div>
                    </div>

                    <!-- Primary Applications -->
                    <div class="mb-12">
                        <h3 class="text-3xl font-bold text-gray-900 mb-8 text-center">Primary Applications</h3>
                        <?php if ($gac_product && !empty($gac_product['applications'])): ?>
                            <?php 
                            $applications = array_map('trim', explode('•', $gac_product['applications']));
                            $applications = array_filter($applications);
                            $app_count = count($applications);
                            $grid_cols = $app_count <= 2 ? 'md:grid-cols-2' : ($app_count <= 3 ? 'md:grid-cols-3' : 'md:grid-cols-2 lg:grid-cols-3');
                            ?>
                            <div class="grid grid-cols-1 <?php echo $grid_cols; ?> gap-8">
                                <?php foreach ($applications as $index => $application): ?>
                                    <div class="product-card bg-white rounded-2xl shadow-lg p-8 h-full">
                                        <div class="text-center">
                                            <div class="product-icon w-24 h-24 rounded-full flex items-center justify-center mx-auto mb-6">
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
                                                <i class="<?php echo $icon; ?> text-4xl text-white"></i>
                                            </div>
                                            <h4 class="text-2xl font-bold text-gray-900 mb-4"><?php echo htmlspecialchars_safe(ucwords($application)); ?></h4>
                                            <?php if ($index === 0 && stripos($application, 'municipal') !== false): ?>
                                                <p class="text-lg font-semibold text-primary mb-4">PRIMARY APPLICATION</p>
                                            <?php endif; ?>
                                            <a href="contact.php" class="inline-block border-2 border-primary text-primary hover:bg-primary hover:text-white font-bold py-2 px-6 rounded-lg transition duration-300 mt-4">Learn More</a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                        <!-- Default Applications -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                            <div class="product-card bg-white rounded-2xl shadow-lg p-8 h-full">
                                <div class="text-center">
                                    <div class="product-icon w-24 h-24 rounded-full flex items-center justify-center mx-auto mb-6">
                                        <i class="fas fa-building text-4xl text-white"></i>
                                    </div>
                                    <h4 class="text-2xl font-bold text-gray-900 mb-4">Municipal Water Treatment</h4>
                                    <p class="text-lg font-semibold text-primary mb-4">PRIMARY APPLICATION</p>
                                    <p class="text-gray-600 mb-6">Our 2mm granulated activated carbon is the preferred choice for municipal water treatment facilities. Proven track record with 200+ municipal clients nationwide.</p>
                                    <ul class="text-left space-y-2 mb-6">
                                        <li class="flex items-center"><i class="fas fa-check text-green-500 mr-2"></i>Drinking water purification</li>
                                        <li class="flex items-center"><i class="fas fa-check text-green-500 mr-2"></i>Chlorine and chloramine removal</li>
                                        <li class="flex items-center"><i class="fas fa-check text-green-500 mr-2"></i>Taste and odor control</li>
                                        <li class="flex items-center"><i class="fas fa-check text-green-500 mr-2"></i>NSF/ANSI certified</li>
                                        <li class="flex items-center"><i class="fas fa-check text-green-500 mr-2"></i>Regulatory compliance</li>
                                    </ul>
                                    <a href="case-studies.php" class="inline-block border-2 border-primary text-primary hover:bg-primary hover:text-white font-bold py-2 px-6 rounded-lg transition duration-300">View Case Studies</a>
                                </div>
                            </div>
                            <div class="product-card bg-white rounded-2xl shadow-lg p-8 h-full">
                                <div class="text-center">
                                    <div class="product-icon w-24 h-24 rounded-full flex items-center justify-center mx-auto mb-6">
                                        <i class="fas fa-industry text-4xl text-white"></i>
                                    </div>
                                    <h4 class="text-2xl font-bold text-gray-900 mb-4">Industrial Wastewater Treatment</h4>
                                    <p class="text-gray-600 mb-6">High-performance 2mm granulated activated carbon for industrial wastewater treatment and process water purification.</p>
                                    <ul class="text-left space-y-2">
                                        <li class="flex items-center"><i class="fas fa-check text-green-500 mr-2"></i>Organic contaminant removal</li>
                                        <li class="flex items-center"><i class="fas fa-check text-green-500 mr-2"></i>High adsorption capacity</li>
                                        <li class="flex items-center"><i class="fas fa-check text-green-500 mr-2"></i>Regenerable options</li>
                                    </ul>
                                </div>
                            </div>
                            <div class="product-card bg-white rounded-2xl shadow-lg p-8 h-full">
                                <div class="text-center">
                                    <div class="product-icon w-24 h-24 rounded-full flex items-center justify-center mx-auto mb-6">
                                        <i class="fas fa-wind text-4xl text-white"></i>
                                    </div>
                                    <h4 class="text-2xl font-bold text-gray-900 mb-4">Air Purification</h4>
                                    <p class="text-gray-600 mb-6">Versatile 2mm granulated activated carbon for industrial air filtration and VOC removal systems.</p>
                                    <ul class="text-left space-y-2">
                                        <li class="flex items-center"><i class="fas fa-check text-green-500 mr-2"></i>VOC and odor removal</li>
                                        <li class="flex items-center"><i class="fas fa-check text-green-500 mr-2"></i>High surface area</li>
                                        <li class="flex items-center"><i class="fas fa-check text-green-500 mr-2"></i>Customizable configurations</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Coconut Husk Products (NEW) -->
                <div id="husk" class="tab-pane hidden">
                    <div class="bg-gradient-to-r from-secondary to-accent text-white rounded-2xl p-8 mb-12 text-center">
                        <h3 class="text-3xl lg:text-4xl font-bold mb-4">Coconut Husk Products</h3>
                        <p class="text-xl text-gray-100">Sustainable Growing Mediums from Our Zero-Waste Operations</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-12">
                        <!-- Coconut Husk Chips -->
                        <div class="product-card bg-white rounded-2xl shadow-lg p-8 h-full">
                            <div class="text-center">
                                <div class="product-icon w-24 h-24 rounded-full flex items-center justify-center mx-auto mb-6">
                                    <i class="fas fa-seedling text-4xl text-white"></i>
                                </div>
                                <h4 class="text-3xl font-bold text-gray-900 mb-4">Coconut Husk Chips</h4>
                                <p class="text-lg font-semibold text-primary mb-4">Premium Growing Medium</p>
                                <p class="text-gray-600 mb-6">High-quality coconut husk chips processed from our zero-waste operations. An excellent sustainable alternative to traditional growing mediums, providing superior water retention and aeration for horticultural applications.</p>
                                
                                <h5 class="text-xl font-bold text-gray-900 mb-4 text-left">Key Features:</h5>
                                <ul class="text-left space-y-2 mb-6">
                                    <li class="flex items-center"><i class="fas fa-check text-green-500 mr-2"></i>Excellent water retention capacity</li>
                                    <li class="flex items-center"><i class="fas fa-check text-green-500 mr-2"></i>Superior aeration properties</li>
                                    <li class="flex items-center"><i class="fas fa-check text-green-500 mr-2"></i>Sustainable and eco-friendly</li>
                                    <li class="flex items-center"><i class="fas fa-check text-green-500 mr-2"></i>Natural fiber composition</li>
                                    <li class="flex items-center"><i class="fas fa-check text-green-500 mr-2"></i>pH balanced and disease-free</li>
                                    <li class="flex items-center"><i class="fas fa-check text-green-500 mr-2"></i>Consistent particle size</li>
                                </ul>

                                <h5 class="text-xl font-bold text-gray-900 mb-4 text-left">Applications:</h5>
                                <ul class="text-left space-y-2 mb-6">
                                    <li class="flex items-center"><i class="fas fa-seedling text-primary mr-2"></i>Horticulture and greenhouse operations</li>
                                    <li class="flex items-center"><i class="fas fa-seedling text-primary mr-2"></i>Commercial agriculture</li>
                                    <li class="flex items-center"><i class="fas fa-seedling text-primary mr-2"></i>Professional gardening</li>
                                    <li class="flex items-center"><i class="fas fa-seedling text-primary mr-2"></i>Container growing</li>
                                </ul>

                                <div class="flex flex-col gap-3 mt-6">
                                    <a href="contact.php" class="bg-primary hover:bg-secondary text-white font-bold py-3 px-8 rounded-lg transition duration-300 text-center">
                                        <i class="fas fa-quote-left mr-2"></i>Request Quote
                                    </a>
                                    <a href="resources.php" class="border-2 border-primary text-primary hover:bg-primary hover:text-white font-bold py-2 px-6 rounded-lg transition duration-300 text-center">
                                        <i class="fas fa-download mr-2"></i>Download Specifications
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Coconut Pit -->
                        <div class="product-card bg-white rounded-2xl shadow-lg p-8 h-full">
                            <div class="text-center">
                                <div class="product-icon w-24 h-24 rounded-full flex items-center justify-center mx-auto mb-6">
                                    <i class="fas fa-leaf text-4xl text-white"></i>
                                </div>
                                <h4 class="text-3xl font-bold text-gray-900 mb-4">Coconut Pit</h4>
                                <p class="text-lg font-semibold text-primary mb-4">Premium Horticultural Growing Medium</p>
                                <p class="text-gray-600 mb-6">Premium-grade coconut pit processed specifically for horticultural applications. This specialized growing medium offers exceptional performance for professional growers, greenhouse operations, and commercial agriculture.</p>
                                
                                <h5 class="text-xl font-bold text-gray-900 mb-4 text-left">Key Features:</h5>
                                <ul class="text-left space-y-2 mb-6">
                                    <li class="flex items-center"><i class="fas fa-check text-green-500 mr-2"></i>Premium quality for professional use</li>
                                    <li class="flex items-center"><i class="fas fa-check text-green-500 mr-2"></i>Optimized particle size distribution</li>
                                    <li class="flex items-center"><i class="fas fa-check text-green-500 mr-2"></i>Superior root development support</li>
                                    <li class="flex items-center"><i class="fas fa-check text-green-500 mr-2"></i>Enhanced nutrient retention</li>
                                    <li class="flex items-center"><i class="fas fa-check text-green-500 mr-2"></i>Long-lasting performance</li>
                                    <li class="flex items-center"><i class="fas fa-check text-green-500 mr-2"></i>Zero-waste sustainable source</li>
                                </ul>

                                <h5 class="text-xl font-bold text-gray-900 mb-4 text-left">Applications:</h5>
                                <ul class="text-left space-y-2 mb-6">
                                    <li class="flex items-center"><i class="fas fa-seedling text-primary mr-2"></i>Professional horticulture</li>
                                    <li class="flex items-center"><i class="fas fa-seedling text-primary mr-2"></i>Greenhouse cultivation</li>
                                    <li class="flex items-center"><i class="fas fa-seedling text-primary mr-2"></i>Commercial growing operations</li>
                                    <li class="flex items-center"><i class="fas fa-seedling text-primary mr-2"></i>Specialized crop production</li>
                                </ul>

                                <div class="flex flex-col gap-3 mt-6">
                                    <a href="contact.php" class="bg-primary hover:bg-secondary text-white font-bold py-3 px-8 rounded-lg transition duration-300 text-center">
                                        <i class="fas fa-quote-left mr-2"></i>Request Quote
                                    </a>
                                    <a href="resources.php" class="border-2 border-primary text-primary hover:bg-primary hover:text-white font-bold py-2 px-6 rounded-lg transition duration-300 text-center">
                                        <i class="fas fa-download mr-2"></i>Download Specifications
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Zero-Waste Message -->
                    <div class="bg-green-50 border-l-4 border-primary rounded-lg p-6">
                        <div class="flex items-start">
                            <i class="fas fa-recycle text-3xl text-primary mr-4 mt-1"></i>
                            <div>
                                <h4 class="text-xl font-bold text-gray-900 mb-2">Sustainable Zero-Waste Operations</h4>
                                <p class="text-gray-700">Our Coconut Husk Products are part of our integrated zero-waste operations. By utilizing all parts of the coconut, we maximize resource efficiency while providing high-quality growing mediums for the horticultural industry. These products represent our commitment to sustainability and environmental responsibility.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Custom Formulations -->
                <div id="custom" class="tab-pane hidden">
                    <div class="bg-gradient-to-r from-accent to-secondary text-white rounded-2xl p-8 mb-12 text-center">
                        <h3 class="text-3xl lg:text-4xl font-bold mb-4">Custom Formulations</h3>
                        <p class="text-xl text-gray-100">Tailored activated carbon solutions for specialized applications</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                        <div class="product-card bg-white rounded-2xl shadow-lg p-8 h-full">
                            <div class="text-center">
                                <div class="product-icon w-24 h-24 rounded-full flex items-center justify-center mx-auto mb-6">
                                    <i class="fas fa-cogs text-4xl text-white"></i>
                                </div>
                                <h4 class="text-2xl font-bold text-gray-900 mb-4">Custom Particle Sizes</h4>
                                <p class="text-gray-600 mb-6">Tailored activated carbon formulations with custom particle sizes designed for your specific application requirements beyond standard 2mm granulated specification.</p>
                                <ul class="text-left space-y-2 mb-6">
                                    <li class="flex items-center"><i class="fas fa-check text-green-500 mr-2"></i>Custom particle size distributions</li>
                                    <li class="flex items-center"><i class="fas fa-check text-green-500 mr-2"></i>Specialized surface chemistry</li>
                                    <li class="flex items-center"><i class="fas fa-check text-green-500 mr-2"></i>Application-specific testing</li>
                                    <li class="flex items-center"><i class="fas fa-check text-green-500 mr-2"></i>Performance optimization</li>
                                </ul>
                                <a href="contact.php" class="inline-block border-2 border-primary text-primary hover:bg-primary hover:text-white font-bold py-2 px-6 rounded-lg transition duration-300">Request Consultation</a>
                            </div>
                        </div>
                        <div class="product-card bg-white rounded-2xl shadow-lg p-8 h-full">
                            <div class="text-center">
                                <div class="product-icon w-24 h-24 rounded-full flex items-center justify-center mx-auto mb-6">
                                    <i class="fas fa-tools text-4xl text-white"></i>
                                </div>
                                <h4 class="text-2xl font-bold text-gray-900 mb-4">System Integration</h4>
                                <p class="text-gray-600 mb-6">Complete activated carbon system design and integration for complex applications.</p>
                                <ul class="text-left space-y-2 mb-6">
                                    <li class="flex items-center"><i class="fas fa-check text-green-500 mr-2"></i>System design</li>
                                    <li class="flex items-center"><i class="fas fa-check text-green-500 mr-2"></i>Installation support</li>
                                    <li class="flex items-center"><i class="fas fa-check text-green-500 mr-2"></i>Performance monitoring</li>
                                </ul>
                                <a href="contact.php" class="inline-block border-2 border-primary text-primary hover:bg-primary hover:text-white font-bold py-2 px-6 rounded-lg transition duration-300">Request Consultation</a>
                            </div>
                        </div>
                        <div class="product-card bg-white rounded-2xl shadow-lg p-8 h-full">
                            <div class="text-center">
                                <div class="product-icon w-24 h-24 rounded-full flex items-center justify-center mx-auto mb-6">
                                    <i class="fas fa-microscope text-4xl text-white"></i>
                                </div>
                                <h4 class="text-2xl font-bold text-gray-900 mb-4">R&D Support</h4>
                                <p class="text-gray-600 mb-6">Research and development support for new activated carbon applications and technologies.</p>
                                <ul class="text-left space-y-2 mb-6">
                                    <li class="flex items-center"><i class="fas fa-check text-green-500 mr-2"></i>Laboratory testing</li>
                                    <li class="flex items-center"><i class="fas fa-check text-green-500 mr-2"></i>Performance analysis</li>
                                    <li class="flex items-center"><i class="fas fa-check text-green-500 mr-2"></i>Technical consultation</li>
                                </ul>
                                <a href="contact.php" class="inline-block border-2 border-primary text-primary hover:bg-primary hover:text-white font-bold py-2 px-6 rounded-lg transition duration-300">Request Consultation</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Product Specifications -->
    <section class="bg-light py-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-4xl lg:text-5xl font-bold text-gray-900 mb-4"><?php echo htmlspecialchars_safe($specifications_title); ?></h2>
                <p class="text-xl text-gray-600 mb-4"><?php echo htmlspecialchars_safe($specifications_subtitle); ?></p>
                <p class="text-lg font-semibold text-primary">Particle Size: <strong>2mm below (granulated)</strong> - Strict Quality Control</p>
            </div>
            <div class="max-w-6xl mx-auto">
                <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[600px]">
                        <thead>
                            <tr class="bg-primary text-white">
                                <th class="px-6 py-4 text-left font-semibold">Property</th>
                                <th class="px-6 py-4 text-center font-semibold">Standard Grade</th>
                                <th class="px-6 py-4 text-center font-semibold">Premium Grade</th>
                                <th class="px-6 py-4 text-center font-semibold">Ultra-Pure Grade</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 font-semibold">Surface Area (m²/g)</td>
                                <td class="px-6 py-4 text-center">800-1000</td>
                                <td class="px-6 py-4 text-center">1000-1200</td>
                                <td class="px-6 py-4 text-center">1200-1500</td>
                            </tr>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 font-semibold">Iodine Number</td>
                                <td class="px-6 py-4 text-center">800-1000</td>
                                <td class="px-6 py-4 text-center">1000-1200</td>
                                <td class="px-6 py-4 text-center">1200-1400</td>
                            </tr>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 font-semibold">Ash Content (%)</td>
                                <td class="px-6 py-4 text-center">≤ 8</td>
                                <td class="px-6 py-4 text-center">≤ 5</td>
                                <td class="px-6 py-4 text-center">≤ 2</td>
                            </tr>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 font-semibold">Moisture (%)</td>
                                <td class="px-6 py-4 text-center">≤ 5</td>
                                <td class="px-6 py-4 text-center">≤ 3</td>
                                <td class="px-6 py-4 text-center">≤ 1</td>
                            </tr>
                            <tr class="hover:bg-gray-50 bg-yellow-50">
                                <td class="px-6 py-4 font-semibold">Particle Size</td>
                                <td class="px-6 py-4 text-center font-bold text-primary">2mm below (granulated)</td>
                                <td class="px-6 py-4 text-center font-bold text-primary">2mm below (granulated)</td>
                                <td class="px-6 py-4 text-center font-bold text-primary">2mm below (granulated)</td>
                            </tr>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 font-semibold">Bulk Density (g/L)</td>
                                <td class="px-6 py-4 text-center">400-500</td>
                                <td class="px-6 py-4 text-center">450-550</td>
                                <td class="px-6 py-4 text-center">500-600</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>

    <!-- Applications -->
    <section class="py-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-4xl lg:text-5xl font-bold text-gray-900 mb-4"><?php echo htmlspecialchars_safe($applications_title); ?></h2>
                <p class="text-xl text-gray-600"><?php echo nl2br_safe($applications_subtitle); ?></p>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-8">
                <div class="bg-gradient-to-br from-primary to-secondary text-white rounded-2xl shadow-lg p-6 text-center hover:transform hover:-translate-y-1 transition duration-300 border-4 border-primary">
                    <div class="application-icon w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4 bg-white">
                        <i class="fas fa-building text-2xl text-primary"></i>
                    </div>
                    <h5 class="text-lg font-bold mb-2">Municipal Water Treatment</h5>
                    <p class="text-xs font-semibold mb-2 bg-white text-primary px-2 py-1 rounded-full inline-block">PRIMARY APPLICATION</p>
                    <p class="text-gray-100 text-sm">Drinking water treatment for cities and towns - 200+ facilities served</p>
                </div>
                <div class="bg-white rounded-2xl shadow-lg p-6 text-center hover:transform hover:-translate-y-1 transition duration-300">
                    <div class="application-icon w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-industry text-2xl text-white"></i>
                    </div>
                    <h5 class="text-lg font-bold text-gray-900 mb-2">Industrial Wastewater</h5>
                    <p class="text-gray-600 text-sm">Treatment of industrial process water</p>
                </div>
                <div class="bg-white rounded-2xl shadow-lg p-6 text-center hover:transform hover:-translate-y-1 transition duration-300">
                    <div class="application-icon w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-hospital text-2xl text-white"></i>
                    </div>
                    <h5 class="text-lg font-bold text-gray-900 mb-2">Healthcare</h5>
                    <p class="text-gray-600 text-sm">Medical air filtration and sterilization</p>
                </div>
                <div class="bg-white rounded-2xl shadow-lg p-6 text-center hover:transform hover:-translate-y-1 transition duration-300">
                    <div class="application-icon w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-flask text-2xl text-white"></i>
                    </div>
                    <h5 class="text-lg font-bold text-gray-900 mb-2">Pharmaceutical</h5>
                    <p class="text-gray-600 text-sm">Drug purification and manufacturing</p>
                </div>
                <div class="bg-white rounded-2xl shadow-lg p-6 text-center hover:transform hover:-translate-y-1 transition duration-300">
                    <div class="application-icon w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-gas-pump text-2xl text-white"></i>
                    </div>
                    <h5 class="text-lg font-bold text-gray-900 mb-2">Oil & Gas</h5>
                    <p class="text-gray-600 text-sm">Fuel purification and gas treatment</p>
                </div>
                <div class="bg-white rounded-2xl shadow-lg p-6 text-center hover:transform hover:-translate-y-1 transition duration-300">
                    <div class="application-icon w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-home text-2xl text-white"></i>
                    </div>
                    <h5 class="text-lg font-bold text-gray-900 mb-2">Residential</h5>
                    <p class="text-gray-600 text-sm">Home water and air filtration</p>
                </div>
                <div class="bg-white rounded-2xl shadow-lg p-6 text-center hover:transform hover:-translate-y-1 transition duration-300">
                    <div class="application-icon w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-fish text-2xl text-white"></i>
                    </div>
                    <h5 class="text-lg font-bold text-gray-900 mb-2">Aquaculture</h5>
                    <p class="text-gray-600 text-sm">Fish farming and aquarium systems</p>
                </div>
                <div class="bg-white rounded-2xl shadow-lg p-6 text-center hover:transform hover:-translate-y-1 transition duration-300">
                    <div class="application-icon w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-leaf text-2xl text-white"></i>
                    </div>
                    <h5 class="text-lg font-bold text-gray-900 mb-2">Environmental</h5>
                    <p class="text-gray-600 text-sm">Pollution control and remediation</p>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="bg-primary text-white py-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-4xl lg:text-5xl font-bold mb-6"><?php echo htmlspecialchars_safe($cta_title); ?></h2>
            <p class="text-xl mb-8"><?php echo htmlspecialchars_safe($cta_description); ?></p>
            <div class="flex flex-col sm:flex-row justify-center gap-4">
                <?php if ($cta_button_1_text): ?>
                    <a href="<?php echo htmlspecialchars_safe($cta_button_1_link); ?>" class="bg-white text-primary hover:bg-gray-100 font-bold py-3 px-8 rounded-lg transition duration-300 inline-block"><?php echo htmlspecialchars_safe($cta_button_1_text); ?></a>
                <?php endif; ?>
                <?php if ($cta_button_2_text): ?>
                    <a href="<?php echo htmlspecialchars_safe($cta_button_2_link); ?>" class="border-2 border-white text-white hover:bg-white hover:text-primary font-bold py-3 px-8 rounded-lg transition duration-300 inline-block"><?php echo htmlspecialchars_safe($cta_button_2_text); ?></a>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <?php include 'includes/footer.php'; ?>

    <script>
        function showTab(tabName) {
            // Hide all tab panes
            const tabPanes = document.querySelectorAll('.tab-pane');
            tabPanes.forEach(pane => pane.classList.add('hidden'));
            
            // Show selected tab pane
            document.getElementById(tabName).classList.remove('hidden');
            
            // Update active state of navigation pills
            const navPills = document.querySelectorAll('.nav-pill');
            navPills.forEach(pill => pill.classList.remove('active'));
            event.target.classList.add('active');
        }

    </script>
</body>
</html> 