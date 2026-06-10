<?php
require_once 'includes/config.php';
$current_page = 'case-studies';

// Get dynamic content
$page_header_title = getPageContent('case-studies', 'page_header_title', 'Municipal Water Treatment Case Studies');
$page_header_subtitle = getPageContent('case-studies', 'page_header_subtitle', 'Real-world success stories from municipal facilities using our activated carbon solutions');
$section_title = getPageContent('case-studies', 'section_title', 'Success Stories');
$section_subtitle = getPageContent('case-studies', 'section_subtitle', 'Discover how municipal water treatment facilities have achieved outstanding results with our 2mm granulated activated carbon');
$cta_title = getPageContent('case-studies', 'cta_title', 'Ready to Achieve Similar Results?');
$cta_description = getPageContent('case-studies', 'cta_description', 'Contact us today to discuss how our activated carbon solutions can benefit your municipal water treatment facility');
$cta_button_1_text = getPageContent('case-studies', 'cta_button_1_text', 'Request Consultation');
$cta_button_1_link = getPageContent('case-studies', 'cta_button_1_link', 'contact.php');
$cta_button_2_text = getPageContent('case-studies', 'cta_button_2_text', 'Download Case Study PDF');
$cta_button_2_link = getPageContent('case-studies', 'cta_button_2_link', 'resources.php');

// Get case studies from database
$case_studies = getCaseStudies();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Municipal water treatment case studies showcasing successful activated carbon solutions. Real-world results from municipal facilities across the nation.">
    <title>Case Studies - Municipal Water Treatment Success Stories | <?php echo htmlspecialchars_safe(getSiteSetting('company_short_name', 'TSACI')); ?></title>
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
        
        .case-study-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .case-study-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-badge {
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

    <!-- Featured Case Studies -->
    <section class="py-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-4xl lg:text-5xl font-bold text-gray-900 mb-4"><?php echo htmlspecialchars_safe($section_title); ?></h2>
                <p class="text-xl text-gray-600"><?php echo htmlspecialchars_safe($section_subtitle); ?></p>
            </div>

            <?php if (!empty($case_studies)): ?>
                <?php foreach ($case_studies as $case): ?>
                    <div class="case-study-card bg-white rounded-2xl shadow-lg overflow-hidden mb-12">
                        <div class="md:flex">
                            <div class="md:w-2/3 p-8">
                                <div class="flex items-center mb-4">
                                    <?php if ($case['is_featured']): ?>
                                        <div class="stat-badge px-4 py-2 rounded-full text-white font-bold mr-4">Featured</div>
                                    <?php endif; ?>
                                    <?php if ($case['industry']): ?>
                                        <span class="text-gray-500"><?php echo htmlspecialchars_safe($case['industry']); ?></span>
                                    <?php endif; ?>
                                </div>
                                <h3 class="text-3xl font-bold text-gray-900 mb-4"><?php echo htmlspecialchars_safe($case['title']); ?></h3>
                                <?php if ($case['client_name']): ?>
                                    <p class="text-gray-500 mb-4">Client: <?php echo htmlspecialchars_safe($case['client_name']); ?><?php if ($case['location']): ?> - <?php echo htmlspecialchars_safe($case['location']); ?><?php endif; ?></p>
                                <?php endif; ?>

                                <?php if ($case['results']): ?>
                                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                                        <?php 
                                        // Parse results for key metrics if available
                                        $results_lines = explode("\n", $case['results']);
                                        foreach (array_slice($results_lines, 0, 4) as $result_line):
                                            $result_line = trim($result_line);
                                            if ($result_line && preg_match('/(\d+[%$]?[\.\d]*[M]?)\s*(.+)/i', $result_line, $matches)):
                                        ?>
                                            <div class="bg-light p-4 rounded-lg text-center">
                                                <div class="text-3xl font-bold text-primary mb-1"><?php echo htmlspecialchars_safe($matches[1]); ?></div>
                                                <div class="text-sm text-gray-600"><?php echo htmlspecialchars_safe($matches[2]); ?></div>
                                            </div>
                                        <?php 
                                            endif;
                                        endforeach; 
                                        ?>
                                    </div>
                                <?php endif; ?>

                                <?php if ($case['challenge']): ?>
                                    <h4 class="text-xl font-bold text-gray-900 mb-3">Challenge</h4>
                                    <p class="text-gray-600 mb-6"><?php echo nl2br_safe($case['challenge']); ?></p>
                                <?php endif; ?>

                                <?php if ($case['solution']): ?>
                                    <h4 class="text-xl font-bold text-gray-900 mb-3">Solution</h4>
                                    <p class="text-gray-600 mb-6"><?php echo nl2br_safe($case['solution']); ?></p>
                                <?php endif; ?>

                                <?php if ($case['results']): ?>
                                    <h4 class="text-xl font-bold text-gray-900 mb-3">Results</h4>
                                    <ul class="text-gray-600 space-y-2 mb-6">
                                        <?php 
                                        $results_list = explode("\n", $case['results']);
                                        foreach ($results_list as $result_item):
                                            $result_item = trim($result_item);
                                            if ($result_item):
                                        ?>
                                            <li class="flex items-start"><i class="fas fa-check-circle text-green-500 mr-2 mt-1"></i><?php echo htmlspecialchars_safe($result_item); ?></li>
                                        <?php 
                                            endif;
                                        endforeach; 
                                        ?>
                                    </ul>
                                <?php endif; ?>

                            </div>
                            <div class="md:w-1/3 bg-light p-8 flex flex-col justify-center">
                                <h4 class="text-xl font-bold text-gray-900 mb-4">Project Details</h4>
                                <div class="space-y-4">
                                    <?php if ($case['industry']): ?>
                                        <div>
                                            <div class="text-sm text-gray-500 mb-1">Industry</div>
                                            <div class="font-semibold text-gray-900"><?php echo htmlspecialchars_safe($case['industry']); ?></div>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($case['client_name']): ?>
                                        <div>
                                            <div class="text-sm text-gray-500 mb-1">Client</div>
                                            <div class="font-semibold text-gray-900"><?php echo htmlspecialchars_safe($case['client_name']); ?></div>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($case['location']): ?>
                                        <div>
                                            <div class="text-sm text-gray-500 mb-1">Location</div>
                                            <div class="font-semibold text-gray-900"><?php echo htmlspecialchars_safe($case['location']); ?></div>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($case['created_at']): ?>
                                        <div>
                                            <div class="text-sm text-gray-500 mb-1">Date</div>
                                            <div class="font-semibold text-gray-900"><?php echo date('Y', strtotime($case['created_at'])); ?></div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <?php if ($case['image_url']): ?>
                                    <div class="mt-6">
                                        <img src="<?php echo htmlspecialchars_safe($case['image_url']); ?>" alt="<?php echo htmlspecialchars_safe($case['title']); ?>" class="w-full rounded-lg">
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="bg-white rounded-2xl shadow-lg p-12 text-center">
                    <p class="text-gray-600 text-lg">No case studies available at this time. Please check back later.</p>
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

