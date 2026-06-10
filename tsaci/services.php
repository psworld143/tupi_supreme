<?php
require_once 'includes/config.php';
$current_page = 'services';

// Get dynamic content
$page_header_title = getPageContent('services', 'page_header_title', 'Our Services');
$page_header_subtitle = getPageContent('services', 'page_header_subtitle', 'Comprehensive support and solutions for all your activated carbon needs');
$section_title = getPageContent('services', 'section_title', 'Comprehensive Services');
$section_subtitle = getPageContent('services', 'section_subtitle', 'From technical consultation to system integration, we provide end-to-end solutions');
$process_title = getPageContent('services', 'process_title', 'Our Service Process');
$process_subtitle = getPageContent('services', 'process_subtitle', 'A systematic approach to delivering exceptional service and solutions');
$features_title = getPageContent('services', 'features_title', 'Why Choose Our Services?');
$features_subtitle = getPageContent('services', 'features_subtitle', 'We deliver exceptional value through our comprehensive service offerings');
$testimonials_title = getPageContent('services', 'testimonials_title', 'Client Testimonials');
$testimonials_subtitle = getPageContent('services', 'testimonials_subtitle', 'What our clients say about our services');
$cta_title = getPageContent('services', 'cta_title', 'Ready to Get Started?');
$cta_description = getPageContent('services', 'cta_description', 'Contact us today to discuss your specific needs and discover how our services can benefit your operations.');
$cta_button_1_text = getPageContent('services', 'cta_button_1_text', 'Request Consultation');
$cta_button_1_link = getPageContent('services', 'cta_button_1_link', 'contact.php');
$cta_button_2_text = getPageContent('services', 'cta_button_2_text', 'Get Quote');
$cta_button_2_link = getPageContent('services', 'cta_button_2_link', 'contact.php');

// Get services from database
$services = getServices();

// Get testimonials from database
$testimonials = [];
$db = getDB();
if ($db) {
    // Check if testimonials table exists
    $table_check = $db->query("SHOW TABLES LIKE 'testimonials'");
    if ($table_check && $table_check->num_rows > 0) {
        $result = $db->query("SELECT * FROM testimonials WHERE is_active = 1 ORDER BY display_order LIMIT 3");
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $testimonials[] = $row;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Services - <?php echo htmlspecialchars_safe(getSiteSetting('company_name', 'Tupi Supreme Activated Carbon, Inc.')); ?></title>
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
        
        .service-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .service-card:hover {
            transform: translateY(-5px);
        }
        
        .service-icon {
            background: linear-gradient(135deg, #8bc34a, #4a7c59);
        }
        
        .process-step::after {
            content: '';
            position: absolute;
            top: 50%;
            right: -25%;
            width: 50%;
            height: 2px;
            background: #2c5530;
            transform: translateY(-50%);
            z-index: 0;
        }
        
        .process-step:last-child::after {
            display: none;
        }
        
        @media (max-width: 1024px) {
            .process-step::after {
                display: none;
            }
        }
        
        .process-icon {
            background: #2c5530;
            position: relative;
        }
        
        .testimonial-avatar {
            background: linear-gradient(135deg, #8bc34a, #4a7c59);
        }
        
        .feature-icon {
            background: #8bc34a;
        }
        
        @media (max-width: 768px) {
            .process-step::after {
                display: none;
            }
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

    <!-- Main Services -->
    <section class="py-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-4xl lg:text-5xl font-bold text-gray-900 mb-4"><?php echo htmlspecialchars_safe($section_title); ?></h2>
                <p class="text-xl text-gray-600"><?php echo htmlspecialchars_safe($section_subtitle); ?></p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <?php foreach ($services as $service): ?>
                    <div class="service-card bg-white rounded-2xl shadow-lg p-8 h-full">
                        <div class="text-center">
                            <?php if ($service['icon']): ?>
                                <div class="service-icon w-24 h-24 rounded-full flex items-center justify-center mx-auto mb-6">
                                    <i class="<?php echo htmlspecialchars_safe($service['icon']); ?> text-4xl text-white"></i>
                                </div>
                            <?php endif; ?>
                            <h4 class="text-2xl font-bold text-gray-900 mb-4"><?php echo htmlspecialchars_safe($service['title']); ?></h4>
                            <?php if ($service['description']): ?>
                                <p class="text-gray-600 mb-6"><?php echo htmlspecialchars_safe($service['description']); ?></p>
                            <?php endif; ?>
                            <?php if ($service['features']): ?>
                                <ul class="text-left space-y-2">
                                    <?php 
                                    $features = explode("\n", $service['features']);
                                    foreach ($features as $feature):
                                        $feature = trim($feature);
                                        if ($feature):
                                    ?>
                                        <li class="flex items-center"><i class="fas fa-check text-green-500 mr-2"></i><?php echo htmlspecialchars_safe($feature); ?></li>
                                    <?php 
                                        endif;
                                    endforeach; 
                                    ?>
                                </ul>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Service Process -->
    <section class="bg-light py-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-4xl lg:text-5xl font-bold text-gray-900 mb-4"><?php echo htmlspecialchars_safe($process_title); ?></h2>
                <p class="text-xl text-gray-600"><?php echo htmlspecialchars_safe($process_subtitle); ?></p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                <div class="process-step text-center p-6 relative">
                    <div class="process-icon w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-4 relative z-20 bg-primary">
                        <i class="fas fa-search text-2xl text-white"></i>
                    </div>
                    <h5 class="text-xl font-bold text-gray-900 mb-2 relative z-10">Assessment</h5>
                    <p class="text-gray-600 relative z-10">We begin by thoroughly assessing your specific needs, requirements, and application challenges.</p>
                </div>
                <div class="process-step text-center p-6 relative">
                    <div class="process-icon w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-4 relative z-20 bg-primary">
                        <i class="fas fa-lightbulb text-2xl text-white"></i>
                    </div>
                    <h5 class="text-xl font-bold text-gray-900 mb-2 relative z-10">Solution Design</h5>
                    <p class="text-gray-600 relative z-10">Our experts design customized solutions tailored to your specific application and requirements.</p>
                </div>
                <div class="process-step text-center p-6 relative">
                    <div class="process-icon w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-4 relative z-20 bg-primary">
                        <i class="fas fa-cogs text-2xl text-white"></i>
                    </div>
                    <h5 class="text-xl font-bold text-gray-900 mb-2 relative z-10">Implementation</h5>
                    <p class="text-gray-600 relative z-10">We implement the solution with precision, ensuring optimal performance and reliability.</p>
                </div>
                <div class="process-step text-center p-6 relative">
                    <div class="process-icon w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-4 relative z-20 bg-primary">
                        <i class="fas fa-chart-line text-2xl text-white"></i>
                    </div>
                    <h5 class="text-xl font-bold text-gray-900 mb-2 relative z-10">Monitoring</h5>
                    <p class="text-gray-600 relative z-10">Continuous monitoring and support to ensure long-term success and optimal performance.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Service Features -->
    <section class="py-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-4xl lg:text-5xl font-bold text-gray-900 mb-4"><?php echo htmlspecialchars_safe($features_title); ?></h2>
                <p class="text-xl text-gray-600"><?php echo htmlspecialchars_safe($features_subtitle); ?></p>
            </div>
            <div class="max-w-4xl mx-auto">
                <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
                    <div class="flex items-center p-6 border-b border-gray-200">
                        <div class="feature-icon w-12 h-12 rounded-full flex items-center justify-center mr-6">
                            <i class="fas fa-award text-xl text-white"></i>
                        </div>
                        <div>
                            <h5 class="text-xl font-bold text-gray-900 mb-2">Expert Team</h5>
                            <p class="text-gray-600">Our team consists of certified professionals with decades of experience in activated carbon applications.</p>
                        </div>
                    </div>
                    <div class="flex items-center p-6 border-b border-gray-200">
                        <div class="feature-icon w-12 h-12 rounded-full flex items-center justify-center mr-6">
                            <i class="fas fa-clock text-xl text-white"></i>
                        </div>
                        <div>
                            <h5 class="text-xl font-bold text-gray-900 mb-2">24/7 Support</h5>
                            <p class="text-gray-600">Round-the-clock technical support and emergency services to ensure your operations never stop.</p>
                        </div>
                    </div>
                    <div class="flex items-center p-6 border-b border-gray-200">
                        <div class="feature-icon w-12 h-12 rounded-full flex items-center justify-center mr-6">
                            <i class="fas fa-shield-alt text-xl text-white"></i>
                        </div>
                        <div>
                            <h5 class="text-xl font-bold text-gray-900 mb-2">Quality Assurance</h5>
                            <p class="text-gray-600">Rigorous quality control processes ensure consistent, reliable service delivery every time.</p>
                        </div>
                    </div>
                    <div class="flex items-center p-6 border-b border-gray-200">
                        <div class="feature-icon w-12 h-12 rounded-full flex items-center justify-center mr-6">
                            <i class="fas fa-leaf text-xl text-white"></i>
                        </div>
                        <div>
                            <h5 class="text-xl font-bold text-gray-900 mb-2">Sustainable Solutions</h5>
                            <p class="text-gray-600">Environmentally responsible service practices that align with your sustainability goals.</p>
                        </div>
                    </div>
                    <div class="flex items-center p-6 border-b border-gray-200">
                        <div class="feature-icon w-12 h-12 rounded-full flex items-center justify-center mr-6">
                            <i class="fas fa-handshake text-xl text-white"></i>
                        </div>
                        <div>
                            <h5 class="text-xl font-bold text-gray-900 mb-2">Long-term Partnership</h5>
                            <p class="text-gray-600">We build lasting relationships with our clients, providing ongoing support and value.</p>
                        </div>
                    </div>
                    <div class="flex items-center p-6">
                        <div class="feature-icon w-12 h-12 rounded-full flex items-center justify-center mr-6">
                            <i class="fas fa-chart-bar text-xl text-white"></i>
                        </div>
                        <div>
                            <h5 class="text-xl font-bold text-gray-900 mb-2">Performance Optimization</h5>
                            <p class="text-gray-600">Continuous improvement services to maximize efficiency and reduce operational costs.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Testimonials -->
    <section class="py-20 bg-light">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-4xl lg:text-5xl font-bold text-gray-900 mb-4"><?php echo htmlspecialchars_safe($testimonials_title); ?></h2>
                <p class="text-xl text-gray-600"><?php echo htmlspecialchars_safe($testimonials_subtitle); ?></p>
            </div>
            <?php if (!empty($testimonials)): ?>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <?php foreach ($testimonials as $testimonial): ?>
                    <div class="bg-white rounded-2xl shadow-lg p-8">
                        <div class="text-center mb-6">
                            <?php if ($testimonial['photo_url']): ?>
                                <img src="<?php echo htmlspecialchars_safe($testimonial['photo_url']); ?>" alt="<?php echo htmlspecialchars_safe($testimonial['name']); ?>" class="w-16 h-16 rounded-full mx-auto mb-4 object-cover">
                            <?php else: ?>
                                <div class="testimonial-avatar w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                                    <i class="fas fa-user text-2xl text-white"></i>
                                </div>
                            <?php endif; ?>
                            <h5 class="text-xl font-bold text-gray-900 mb-1"><?php echo htmlspecialchars_safe($testimonial['name']); ?></h5>
                            <?php if ($testimonial['position']): ?>
                                <p class="text-gray-500"><?php echo htmlspecialchars_safe($testimonial['position']); ?></p>
                            <?php endif; ?>
                            <?php if ($testimonial['company']): ?>
                                <p class="text-gray-400 text-sm"><?php echo htmlspecialchars_safe($testimonial['company']); ?></p>
                            <?php endif; ?>
                        </div>
                        <?php if ($testimonial['testimonial']): ?>
                            <p class="text-center text-gray-600">"<?php echo htmlspecialchars_safe($testimonial['testimonial']); ?>"</p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
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

    <!-- Mobile Menu JavaScript -->
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
</body>
</html> 