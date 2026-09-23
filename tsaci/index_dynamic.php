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
        .hero-gradient {
            background: #2c5530;
        }
        
        .hero-pattern {
            background-image: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="50" cy="50" r="1" fill="rgba(255,255,255,0.1)"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
        }
        
        .feature-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .feature-card:hover {
            transform: translateY(-5px);
        }
        
        .feature-icon {
            background: #4a7c59;
        }
    </style>
</head>
<body class="font-sans">
    <?php include 'includes/navbar.php'; ?>

    <!-- Hero Section -->
    <section class="hero-gradient text-white relative overflow-hidden pt-24 pb-20">
        <div class="hero-pattern absolute inset-0 opacity-30"></div>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                <div>
                    <h1 class="text-5xl lg:text-6xl font-bold mb-6"><?php echo htmlspecialchars_safe($hero_title); ?></h1>
                    <p class="text-xl mb-8 text-gray-100"><?php echo htmlspecialchars_safe($hero_description); ?></p>
                    <div class="flex flex-col sm:flex-row gap-4">
                        <a href="<?php echo htmlspecialchars_safe($hero_cta_primary_link); ?>" class="bg-primary hover:bg-secondary text-white font-bold py-3 px-8 rounded-lg transition duration-300 text-center"><?php echo htmlspecialchars_safe($hero_cta_primary_text); ?></a>
                        <a href="<?php echo htmlspecialchars_safe($hero_cta_secondary_link); ?>" class="border-2 border-white text-white hover:bg-white hover:text-primary font-bold py-3 px-8 rounded-lg transition duration-300 text-center"><?php echo htmlspecialchars_safe($hero_cta_secondary_text); ?></a>
                    </div>
                </div>
                <div class="text-center">
                    <i class="fas fa-water text-8xl text-white opacity-75"></i>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="py-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-4xl lg:text-5xl font-bold text-gray-900 mb-4"><?php echo htmlspecialchars_safe($features_title); ?></h2>
                <p class="text-xl text-gray-600"><?php echo htmlspecialchars_safe($features_subtitle); ?></p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                <?php foreach ($features as $feature): ?>
                    <div class="feature-card bg-white rounded-2xl shadow-lg p-8 h-full">
                        <div class="text-center">
                            <?php if ($feature['icon']): ?>
                                <div class="feature-icon w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-6">
                                    <i class="<?php echo htmlspecialchars_safe($feature['icon']); ?> text-3xl text-white"></i>
                                </div>
                            <?php endif; ?>
                            <h3 class="text-2xl font-bold text-gray-900 mb-4"><?php echo htmlspecialchars_safe($feature['title']); ?></h3>
                            <p class="text-gray-600"><?php echo htmlspecialchars_safe($feature['description']); ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="bg-light py-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-8">
                <?php foreach ($statistics as $stat): ?>
                    <div class="text-center">
                        <div class="text-5xl font-bold text-primary mb-2"><?php echo htmlspecialchars_safe($stat['value']); ?></div>
                        <div class="text-lg font-medium text-secondary"><?php echo htmlspecialchars_safe($stat['label']); ?></div>
                        <?php if ($stat['description']): ?>
                            <div class="text-sm text-gray-500 mt-1"><?php echo htmlspecialchars_safe($stat['description']); ?></div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Products Preview -->
    <section class="py-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-4xl lg:text-5xl font-bold text-gray-900 mb-4"><?php echo htmlspecialchars_safe($products_title); ?></h2>
                <p class="text-xl text-gray-600"><?php echo htmlspecialchars_safe($products_subtitle); ?></p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <?php foreach ($featured_products as $product): ?>
                    <div class="bg-white rounded-lg shadow-lg p-8 h-full">
                        <div class="text-center">
                            <i class="fas fa-cube text-5xl text-primary mb-6"></i>
                            <h3 class="text-2xl font-bold text-gray-900 mb-4"><?php echo htmlspecialchars_safe($product['name']); ?></h3>
                            <p class="text-gray-600 mb-4"><?php echo htmlspecialchars_safe($product['description']); ?></p>
                            <a href="products.php#<?php echo htmlspecialchars_safe($product['slug']); ?>" class="inline-block border-2 border-primary text-primary hover:bg-primary hover:text-white font-bold py-2 px-6 rounded-lg transition duration-300">View Details</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="bg-primary text-white py-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-4xl lg:text-5xl font-bold mb-6"><?php echo htmlspecialchars_safe($cta_title); ?></h2>
            <p class="text-xl mb-8"><?php echo htmlspecialchars_safe($cta_description); ?></p>
            <div class="flex flex-col sm:flex-row justify-center gap-4">
                <a href="contact.php" class="bg-white text-primary hover:bg-gray-100 font-bold py-3 px-8 rounded-lg transition duration-300 inline-block">Request Quote</a>
                <a href="resources.php" class="border-2 border-white text-white hover:bg-white hover:text-primary font-bold py-3 px-8 rounded-lg transition duration-300 inline-block">Download Technical Specs</a>
                <a href="contact.php" class="border-2 border-white text-white hover:bg-white hover:text-primary font-bold py-3 px-8 rounded-lg transition duration-300 inline-block">Contact Sales Team</a>
            </div>
        </div>
    </section>

    <?php include 'includes/footer.php'; ?>
</body>
</html>

