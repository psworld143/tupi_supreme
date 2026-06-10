<?php
require_once 'includes/config.php';
$current_page = 'resources';

// Get dynamic content
$page_header_title = getPageContent('resources', 'page_header_title', 'Resources & Documentation');
$page_header_subtitle = getPageContent('resources', 'page_header_subtitle', 'Download technical data sheets, product specifications, and application guides');
$data_sheets_title = getPageContent('resources', 'data_sheets_title', 'Technical Data Sheets');
$data_sheets_subtitle = getPageContent('resources', 'data_sheets_subtitle', 'Comprehensive technical specifications for our activated carbon products');
$catalogs_title = getPageContent('resources', 'catalogs_title', 'Product Catalogs');
$catalogs_subtitle = getPageContent('resources', 'catalogs_subtitle', 'Comprehensive product catalogs and brochures');
$guides_title = getPageContent('resources', 'guides_title', 'Application Guides');
$guides_subtitle = getPageContent('resources', 'guides_subtitle', 'Detailed guides for implementing activated carbon in various applications');
$faqs_title = getPageContent('resources', 'faqs_title', 'Frequently Asked Questions');
$faqs_subtitle = getPageContent('resources', 'faqs_subtitle', 'Common questions about our products and services');
$cta_title = getPageContent('resources', 'cta_title', 'Need More Information?');
$cta_description = getPageContent('resources', 'cta_description', 'Contact our technical team for additional documentation or customized information for your specific application');
$cta_button_1_text = getPageContent('resources', 'cta_button_1_text', 'Request Technical Consultation');
$cta_button_1_link = getPageContent('resources', 'cta_button_1_link', 'contact.php');
$cta_button_2_text = getPageContent('resources', 'cta_button_2_text', 'Contact Sales Team');
$cta_button_2_link = getPageContent('resources', 'cta_button_2_link', 'contact.php');

// Get resources by category
$data_sheets = getResources('Technical Data Sheets');
$catalogs = getResources('Product Catalogs');
$guides = getResources('Application Guides');

// Get FAQs
$faqs = getFAQs();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo htmlspecialchars_safe(getPageContent('resources', 'meta_description', 'Download technical data sheets, product catalogs, application guides, and resources for TSACI activated carbon products. Essential documentation for municipal water treatment facilities.')); ?>">
    <title>Resources - Technical Documentation & Downloads | <?php echo htmlspecialchars_safe(getSiteSetting('company_short_name', 'TSACI')); ?></title>
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
        
        .resource-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .resource-card:hover {
            transform: translateY(-5px);
        }
        
        .download-icon {
            background: linear-gradient(135deg, #8bc34a, #4a7c59);
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

    <!-- Technical Data Sheets -->
    <section class="py-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-4xl lg:text-5xl font-bold text-gray-900 mb-4"><?php echo htmlspecialchars_safe($data_sheets_title); ?></h2>
                <p class="text-xl text-gray-600"><?php echo htmlspecialchars_safe($data_sheets_subtitle); ?></p>
            </div>
            <?php if (!empty($data_sheets)): ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <?php foreach ($data_sheets as $resource): ?>
                        <div class="resource-card bg-white rounded-2xl shadow-lg p-8">
                            <div class="text-center mb-6">
                                <div class="download-icon w-20 h-20 rounded-full flex items-center justify-center mx-auto">
                                    <i class="fas fa-file-pdf text-3xl text-white"></i>
                                </div>
                            </div>
                            <h3 class="text-2xl font-bold text-gray-900 mb-4 text-center"><?php echo htmlspecialchars_safe($resource['title']); ?></h3>
                            <?php if ($resource['file_type']): ?>
                                <p class="text-gray-600 mb-4 text-center"><?php echo htmlspecialchars_safe($resource['file_type']); ?></p>
                            <?php endif; ?>
                            <?php if ($resource['description']): ?>
                                <p class="text-sm text-gray-500 mb-6"><?php echo nl2br_safe($resource['description']); ?></p>
                            <?php endif; ?>
                            <?php 
                            $downloadText = getPageContent('resources', 'download_button_text', 'Download');
                            $fileType = $resource['file_type'] ?: 'PDF';
                            ?>
                            <a href="<?php echo htmlspecialchars_safe($resource['file_url'] ?: '#'); ?>" 
                               class="block w-full bg-primary hover:bg-secondary text-white font-bold py-3 px-6 rounded-lg transition duration-300 text-center"
                               <?php if ($resource['file_url'] && $resource['file_url'] !== '#'): ?>download<?php endif; ?>>
                                <i class="fas fa-download mr-2"></i><?php echo htmlspecialchars_safe($downloadText); ?> <?php echo htmlspecialchars_safe($fileType); ?>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-12">
                    <p class="text-gray-600">No technical data sheets available at this time.</p>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Product Catalogs -->
    <section class="bg-light py-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-4xl lg:text-5xl font-bold text-gray-900 mb-4"><?php echo htmlspecialchars_safe($catalogs_title); ?></h2>
                <p class="text-xl text-gray-600"><?php echo htmlspecialchars_safe($catalogs_subtitle); ?></p>
            </div>
            <?php if (!empty($catalogs)): ?>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <?php foreach ($catalogs as $catalog): ?>
                        <div class="resource-card bg-white rounded-2xl shadow-lg p-8">
                            <div class="flex items-start">
                                <div class="download-icon w-16 h-16 rounded-full flex items-center justify-center mr-6 flex-shrink-0">
                                    <i class="fas fa-book text-2xl text-white"></i>
                                </div>
                                <div class="flex-1">
                                    <h3 class="text-2xl font-bold text-gray-900 mb-3"><?php echo htmlspecialchars_safe($catalog['title']); ?></h3>
                                    <?php if ($catalog['description']): ?>
                                        <p class="text-gray-600 mb-4"><?php echo nl2br_safe($catalog['description']); ?></p>
                                    <?php endif; ?>
                                    <?php 
                                    $downloadText = getPageContent('resources', 'download_button_text', 'Download');
                                    $fileType = $catalog['file_type'] ?: 'PDF';
                                    ?>
                                    <a href="<?php echo htmlspecialchars_safe($catalog['file_url'] ?: '#'); ?>" 
                                       class="inline-block bg-primary hover:bg-secondary text-white font-bold py-2 px-6 rounded-lg transition duration-300"
                                       <?php if ($catalog['file_url'] && $catalog['file_url'] !== '#'): ?>download<?php endif; ?>>
                                        <i class="fas fa-download mr-2"></i><?php echo htmlspecialchars_safe($downloadText); ?> <?php echo htmlspecialchars_safe($fileType); ?>
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-12">
                    <p class="text-gray-600">No product catalogs available at this time.</p>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Application Guides -->
    <section class="py-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-4xl lg:text-5xl font-bold text-gray-900 mb-4"><?php echo htmlspecialchars_safe($guides_title); ?></h2>
                <p class="text-xl text-gray-600"><?php echo htmlspecialchars_safe($guides_subtitle); ?></p>
            </div>
            <?php if (!empty($guides)): ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <?php foreach ($guides as $guide): ?>
                        <div class="resource-card bg-white rounded-2xl shadow-lg p-8">
                            <div class="text-center mb-6">
                                <i class="fas fa-book text-5xl text-primary"></i>
                            </div>
                            <h3 class="text-xl font-bold text-gray-900 mb-3 text-center"><?php echo htmlspecialchars_safe($guide['title']); ?></h3>
                            <?php if ($guide['description']): ?>
                                <p class="text-gray-600 mb-4 text-center text-sm"><?php echo nl2br_safe($guide['description']); ?></p>
                            <?php endif; ?>
                            <?php 
                            $downloadGuideText = getPageContent('resources', 'download_guide_text', 'Download Guide');
                            ?>
                            <a href="<?php echo htmlspecialchars_safe($guide['file_url'] ?: '#'); ?>" 
                               class="block w-full border-2 border-primary text-primary hover:bg-primary hover:text-white font-bold py-2 px-6 rounded-lg transition duration-300 text-center"
                               <?php if ($guide['file_url'] && $guide['file_url'] !== '#'): ?>download<?php endif; ?>>
                                <i class="fas fa-download mr-2"></i><?php echo htmlspecialchars_safe($downloadGuideText); ?>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-12">
                    <p class="text-gray-600">No application guides available at this time.</p>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- FAQs Section -->
    <section class="bg-light py-20">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-4xl lg:text-5xl font-bold text-gray-900 mb-4"><?php echo htmlspecialchars_safe($faqs_title); ?></h2>
                <p class="text-xl text-gray-600"><?php echo htmlspecialchars_safe($faqs_subtitle); ?></p>
            </div>
            <?php if (!empty($faqs)): ?>
                <div class="space-y-4">
                    <?php foreach ($faqs as $index => $faq): 
                        $faqId = 'faq' . ($index + 1);
                    ?>
                        <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                            <button class="w-full px-6 py-4 text-left bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-primary" onclick="toggleFAQ('<?php echo $faqId; ?>')">
                                <div class="flex justify-between items-center">
                                    <span class="text-lg font-semibold text-gray-900"><?php echo htmlspecialchars_safe($faq['question']); ?></span>
                                    <i class="fas fa-chevron-down text-primary transform transition-transform" id="<?php echo $faqId; ?>-icon"></i>
                                </div>
                            </button>
                            <div class="px-6 pb-4 hidden" id="<?php echo $faqId; ?>-content">
                                <p class="text-gray-600"><?php echo nl2br_safe($faq['answer']); ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-12">
                    <p class="text-gray-600">No FAQs available at this time.</p>
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

    <script>
        function toggleFAQ(faqId) {
            const content = document.getElementById(faqId + '-content');
            const icon = document.getElementById(faqId + '-icon');
            
            if (content.classList.contains('hidden')) {
                content.classList.remove('hidden');
                icon.style.transform = 'rotate(180deg)';
            } else {
                content.classList.add('hidden');
                icon.style.transform = 'rotate(0deg)';
            }
        }

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

