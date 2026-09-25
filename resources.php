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
// Catch-all: resources with a custom (non-known) category
$other_resources = getOtherResources();

// Other Resources section copy (editable via page_content)
$other_title = getPageContent('resources', 'other_title', 'Other Resources');
$other_subtitle = getPageContent('resources', 'other_subtitle', 'Additional downloadable resources and documents');

// Get FAQs
$faqs = getFAQs();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/png" href="uploads/images/favicon.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo htmlspecialchars_safe(getPageContent('resources', 'meta_description', 'Download technical data sheets, product catalogs, application guides, and resources for TSACI activated carbon products. Essential documentation for municipal water treatment facilities.')); ?>">
    <title>Resources - Technical Documentation & Downloads | <?php echo htmlspecialchars_safe(getSiteSetting('company_short_name', 'TSACI')); ?></title>
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
            background: #23332c;
        }

        .page-header-pattern {
            background-image: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="50" cy="50" r="1" fill="rgba(255,255,255,0.1)"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
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

        /* Resource cards */
        .resource-card {
            transition: transform 0.35s cubic-bezier(0.4, 0, 0.2, 1), border-color 0.35s ease, box-shadow 0.35s ease;
        }
        .resource-card:hover {
            transform: translateY(-6px);
            border-color: #3d7a66;
            box-shadow: 0 12px 32px -12px rgba(35, 51, 44, 0.15);
        }
        .resource-card:hover .download-icon {
            transform: scale(1.08) rotate(-3deg);
        }
        .download-icon {
            background: #3d7a66;
            transition: transform 0.35s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* FAQ accordion — smooth expand via grid-rows, no JS height math */
        .faq-item {
            transition: border-color 0.3s ease, background-color 0.3s ease;
        }
        .faq-item:hover {
            border-color: rgba(139, 195, 74, 0.35);
        }
        .faq-item.open {
            border-color: rgba(139, 195, 74, 0.45);
            background-color: rgba(255, 255, 255, 0.08);
        }
        .faq-content {
            display: grid;
            grid-template-rows: 0fr;
            transition: grid-template-rows 0.35s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .faq-item.open .faq-content {
            grid-template-rows: 1fr;
        }
        .faq-content-inner {
            overflow: hidden;
        }
        .faq-icon {
            transition: transform 0.35s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .faq-item.open .faq-icon {
            transform: rotate(180deg);
        }
        .faq-button:focus-visible {
            outline: 2px solid #8bc34a;
            outline-offset: -2px;
        }
        @media (prefers-reduced-motion: reduce) {
            .faq-content, .faq-icon { transition: none; }
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
        <div class="page-header-pattern absolute inset-0 opacity-30"></div>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="text-center">
                <span class="eyebrow bg-white/15 text-white/90 mb-5 fade-in fade-in-delay-1">
                    <i class="fas fa-file-download text-xs"></i> Documentation
                </span>
                <h1 class="text-4xl lg:text-6xl font-bold mb-5 leading-tight mt-4 fade-in fade-in-delay-2"><?php echo htmlspecialchars_safe($page_header_title); ?></h1>
                <p class="text-lg lg:text-xl text-white/85 max-w-2xl mx-auto fade-in fade-in-delay-3"><?php echo htmlspecialchars_safe($page_header_subtitle); ?></p>
            </div>
        </div>
    </section>

    <!-- Technical Data Sheets -->
    <section id="data-sheets" class="py-20 lg:py-24 relative overflow-hidden">
        <!-- Subtle dot grid backdrop -->
        <div class="absolute inset-0 dot-grid opacity-40"></div>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative">
            <div class="text-center mb-16 reveal">
                <span class="eyebrow mb-4">
                    <i class="fas fa-file-pdf text-xs"></i> Data Sheets
                </span>
                <h2 class="text-3xl lg:text-4xl font-bold text-[#23332c] mb-4 mt-4"><?php echo htmlspecialchars_safe($data_sheets_title); ?></h2>
                <p class="text-lg text-[#7d8b84] max-w-2xl mx-auto"><?php echo htmlspecialchars_safe($data_sheets_subtitle); ?></p>
            </div>
            <?php if (!empty($data_sheets)): ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($data_sheets as $i => $resource): ?>
                        <div class="resource-card bg-white border border-[#e6ece8] rounded-2xl p-8 reveal <?php echo 'reveal-delay-' . ((($i % 3) + 1)); ?>">
                            <div class="text-center mb-6">
                                <div class="download-icon w-16 h-16 rounded-2xl flex items-center justify-center mx-auto">
                                    <i class="fas fa-file-pdf text-2xl text-white"></i>
                                </div>
                            </div>
                            <h3 class="text-xl font-semibold text-[#23332c] mb-3 text-center"><?php echo htmlspecialchars_safe($resource['title']); ?></h3>
                            <?php if ($resource['file_type']): ?>
                                <p class="text-[#8a978f] mb-3 text-center text-sm"><?php echo htmlspecialchars_safe($resource['file_type']); ?></p>
                            <?php endif; ?>
                            <?php if ($resource['description']): ?>
                                <p class="text-[#7d8b84] mb-6 text-sm leading-relaxed"><?php echo nl2br_safe($resource['description']); ?></p>
                            <?php endif; ?>
                            <?php 
                            $downloadText = getPageContent('resources', 'download_button_text', 'Download');
                            $fileType = $resource['file_type'] ?: 'PDF';
                            ?>
                            <a href="<?php echo htmlspecialchars_safe($resource['file_url'] ?: '#'); ?>" 
                               class="block w-full bg-[#23332c] hover:bg-[#3a4a41] text-white font-medium py-3 px-6 rounded-full transition-colors text-center inline-flex items-center justify-center gap-2"
                               <?php if ($resource['file_url'] && $resource['file_url'] !== '#'): ?>download<?php endif; ?>>
                                <i class="fas fa-download text-xs"></i><?php echo htmlspecialchars_safe($downloadText); ?> <?php echo htmlspecialchars_safe($fileType); ?>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-12 reveal">
                    <div class="w-16 h-16 rounded-full bg-[#eef3f0] flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-file-pdf text-2xl text-[#60796e]"></i>
                    </div>
                    <p class="text-[#7d8b84]">No technical data sheets available at this time.</p>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Product Catalogs -->
    <section id="catalogs" class="bg-[#f5f7f5] py-20 lg:py-24">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16 reveal">
                <span class="eyebrow mb-4">
                    <i class="fas fa-book text-xs"></i> Catalogs
                </span>
                <h2 class="text-3xl lg:text-4xl font-bold text-[#23332c] mb-4 mt-4"><?php echo htmlspecialchars_safe($catalogs_title); ?></h2>
                <p class="text-lg text-[#7d8b84] max-w-2xl mx-auto"><?php echo htmlspecialchars_safe($catalogs_subtitle); ?></p>
            </div>
            <?php if (!empty($catalogs)): ?>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <?php foreach ($catalogs as $i => $catalog): ?>
                        <div class="resource-card bg-white border border-[#e6ece8] rounded-2xl p-8 reveal <?php echo 'reveal-delay-' . ((($i % 2) + 1)); ?>">
                            <div class="flex items-start">
                                <div class="download-icon w-14 h-14 rounded-2xl flex items-center justify-center mr-5 flex-shrink-0">
                                    <i class="fas fa-book text-xl text-white"></i>
                                </div>
                                <div class="flex-1">
                                    <h3 class="text-xl font-semibold text-[#23332c] mb-2"><?php echo htmlspecialchars_safe($catalog['title']); ?></h3>
                                    <?php if ($catalog['description']): ?>
                                        <p class="text-[#7d8b84] mb-5 text-sm leading-relaxed"><?php echo nl2br_safe($catalog['description']); ?></p>
                                    <?php endif; ?>
                                    <?php 
                                    $downloadText = getPageContent('resources', 'download_button_text', 'Download');
                                    $fileType = $catalog['file_type'] ?: 'PDF';
                                    ?>
                                    <a href="<?php echo htmlspecialchars_safe($catalog['file_url'] ?: '#'); ?>" 
                                       class="inline-flex items-center gap-2 bg-[#23332c] hover:bg-[#3a4a41] text-white font-medium py-2.5 px-5 rounded-full transition-colors text-sm"
                                       <?php if ($catalog['file_url'] && $catalog['file_url'] !== '#'): ?>download<?php endif; ?>>
                                        <i class="fas fa-download text-xs"></i><?php echo htmlspecialchars_safe($downloadText); ?> <?php echo htmlspecialchars_safe($fileType); ?>
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-12 reveal">
                    <div class="w-16 h-16 rounded-full bg-[#eef3f0] flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-book text-2xl text-[#60796e]"></i>
                    </div>
                    <p class="text-[#7d8b84]">No product catalogs available at this time.</p>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Application Guides -->
    <section id="guides" class="py-20 lg:py-24 relative overflow-hidden">
        <div class="absolute inset-0 dot-grid opacity-40"></div>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative">
            <div class="text-center mb-16 reveal">
                <span class="eyebrow mb-4">
                    <i class="fas fa-map-signs text-xs"></i> Guides
                </span>
                <h2 class="text-3xl lg:text-4xl font-bold text-[#23332c] mb-4 mt-4"><?php echo htmlspecialchars_safe($guides_title); ?></h2>
                <p class="text-lg text-[#7d8b84] max-w-2xl mx-auto"><?php echo htmlspecialchars_safe($guides_subtitle); ?></p>
            </div>
            <?php if (!empty($guides)): ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($guides as $i => $guide): ?>
                        <div class="resource-card bg-white border border-[#e6ece8] rounded-2xl p-8 reveal <?php echo 'reveal-delay-' . ((($i % 3) + 1)); ?>">
                            <div class="text-center mb-6">
                                <div class="download-icon w-16 h-16 rounded-2xl flex items-center justify-center mx-auto">
                                    <i class="fas fa-book text-2xl text-white"></i>
                                </div>
                            </div>
                            <h3 class="text-lg font-semibold text-[#23332c] mb-3 text-center"><?php echo htmlspecialchars_safe($guide['title']); ?></h3>
                            <?php if ($guide['description']): ?>
                                <p class="text-[#7d8b84] mb-6 text-center text-sm leading-relaxed"><?php echo nl2br_safe($guide['description']); ?></p>
                            <?php endif; ?>
                            <?php 
                            $downloadGuideText = getPageContent('resources', 'download_guide_text', 'Download Guide');
                            ?>
                            <a href="<?php echo htmlspecialchars_safe($guide['file_url'] ?: '#'); ?>" 
                               class="block w-full border border-[#d6ded9] text-[#23332c] hover:bg-[#23332c] hover:text-white hover:border-[#23332c] font-medium py-2.5 px-6 rounded-full transition-colors text-center inline-flex items-center justify-center gap-2 text-sm"
                               <?php if ($guide['file_url'] && $guide['file_url'] !== '#'): ?>download<?php endif; ?>>
                                <i class="fas fa-download text-xs"></i><?php echo htmlspecialchars_safe($downloadGuideText); ?>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-12 reveal">
                    <div class="w-16 h-16 rounded-full bg-[#eef3f0] flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-map-signs text-2xl text-[#60796e]"></i>
                    </div>
                    <p class="text-[#7d8b84]">No application guides available at this time.</p>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Other Resources (catch-all for custom categories) -->
    <section id="other-resources" class="bg-[#f5f7f5] py-20 lg:py-24">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16 reveal">
                <span class="eyebrow mb-4">
                    <i class="fas fa-folder-open text-xs"></i> More
                </span>
                <h2 class="text-3xl lg:text-4xl font-bold text-[#23332c] mb-4 mt-4"><?php echo htmlspecialchars_safe($other_title); ?></h2>
                <p class="text-lg text-[#7d8b84] max-w-2xl mx-auto"><?php echo htmlspecialchars_safe($other_subtitle); ?></p>
            </div>
            <?php if (!empty($other_resources)): ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($other_resources as $i => $resource): ?>
                        <div class="resource-card bg-white border border-[#e6ece8] rounded-2xl p-8 reveal <?php echo 'reveal-delay-' . ((($i % 3) + 1)); ?>">
                            <div class="flex items-center justify-between mb-4">
                                <div class="download-icon w-14 h-14 rounded-2xl flex items-center justify-center">
                                    <i class="fas fa-file-alt text-xl text-white"></i>
                                </div>
                                <?php if ($resource['category']): ?>
                                    <span class="text-xs font-semibold text-[#3d7a66] bg-[#eef3f0] px-3 py-1 rounded-full"><?php echo htmlspecialchars_safe($resource['category']); ?></span>
                                <?php endif; ?>
                            </div>
                            <h3 class="text-lg font-semibold text-[#23332c] mb-2"><?php echo htmlspecialchars_safe($resource['title']); ?></h3>
                            <?php if ($resource['file_type']): ?>
                                <p class="text-[#8a978f] mb-2 text-sm"><?php echo htmlspecialchars_safe($resource['file_type']); ?></p>
                            <?php endif; ?>
                            <?php if ($resource['description']): ?>
                                <p class="text-[#7d8b84] mb-6 text-sm leading-relaxed"><?php echo nl2br_safe($resource['description']); ?></p>
                            <?php endif; ?>
                            <?php
                            $downloadOtherText = getPageContent('resources', 'download_button_text', 'Download');
                            $otherFileType = $resource['file_type'] ?: 'File';
                            ?>
                            <a href="<?php echo htmlspecialchars_safe($resource['file_url'] ?: '#'); ?>"
                               class="inline-flex items-center gap-2 bg-[#23332c] hover:bg-[#3a4a41] text-white font-medium py-2.5 px-5 rounded-full transition-colors text-sm"
                               <?php if ($resource['file_url'] && $resource['file_url'] !== '#'): ?>download<?php endif; ?>>
                                <i class="fas fa-download text-xs"></i><?php echo htmlspecialchars_safe($downloadOtherText); ?> <?php echo htmlspecialchars_safe($otherFileType); ?>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-12 reveal">
                    <div class="w-16 h-16 rounded-full bg-[#eef3f0] flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-folder-open text-2xl text-[#60796e]"></i>
                    </div>
                    <p class="text-[#7d8b84]">No additional resources at this time.</p>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- FAQs Section -->
    <section id="faqs" class="bg-[#23332c] text-white py-20 lg:py-24 relative overflow-hidden">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 relative">
            <div class="text-center mb-16 reveal">
                <span class="eyebrow bg-white/15 text-white/90 mb-4">
                    <i class="fas fa-question-circle text-xs"></i> FAQ
                </span>
                <h2 class="text-3xl lg:text-4xl font-bold mb-4 mt-4"><?php echo htmlspecialchars_safe($faqs_title); ?></h2>
                <p class="text-lg text-white/70 max-w-2xl mx-auto"><?php echo htmlspecialchars_safe($faqs_subtitle); ?></p>
            </div>
            <?php if (!empty($faqs)): ?>
                <div class="space-y-4">
                    <?php foreach ($faqs as $index => $faq): 
                        $faqId = 'faq' . ($index + 1);
                    ?>
                        <div class="faq-item bg-white/5 backdrop-blur-sm border border-white/10 rounded-2xl overflow-hidden reveal <?php echo 'reveal-delay-' . ((($index % 3) + 1)); ?>">
                            <button class="faq-button w-full px-6 py-5 text-left" id="<?php echo $faqId; ?>-button" aria-expanded="false" aria-controls="<?php echo $faqId; ?>-content" onclick="toggleFAQ(this)">
                                <div class="flex justify-between items-center gap-4">
                                    <span class="min-w-0">
                                        <?php if (!empty($faq['category'])): ?>
                                            <span class="inline-block text-[11px] font-medium uppercase tracking-wider text-[#8bc34a] bg-[#8bc34a]/15 rounded-full px-2.5 py-0.5 mb-2"><?php echo htmlspecialchars_safe($faq['category']); ?></span>
                                        <?php endif; ?>
                                        <span class="block text-base font-semibold text-white pr-2"><?php echo htmlspecialchars_safe($faq['question']); ?></span>
                                    </span>
                                    <span class="w-8 h-8 rounded-full bg-white/10 flex items-center justify-center flex-shrink-0">
                                        <i class="fas fa-chevron-down faq-icon text-[#8bc34a] text-sm"></i>
                                    </span>
                                </div>
                            </button>
                            <div class="faq-content" id="<?php echo $faqId; ?>-content" role="region" aria-labelledby="<?php echo $faqId; ?>-button">
                                <div class="faq-content-inner">
                                    <p class="px-6 pb-5 text-white/70 leading-relaxed text-sm"><?php echo nl2br_safe($faq['answer']); ?></p>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <p class="text-center text-white/60 text-sm mt-10 reveal">
                    Still have questions? <a href="contact.php" class="text-[#8bc34a] font-medium hover:underline">Contact our team</a>
                </p>
            <?php else: ?>
                <div class="text-center py-12 reveal">
                    <div class="w-16 h-16 rounded-full bg-white/10 flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-question-circle text-2xl text-white/60"></i>
                    </div>
                    <p class="text-white/70">No FAQs available at this time.</p>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- CTA Section -->
    <section id="cta" class="relative overflow-hidden py-20 lg:py-24">
        <div class="absolute inset-0 page-header-gradient"></div>
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
                        <i class="fas fa-phone text-xs"></i>
                        <?php echo htmlspecialchars_safe($cta_button_2_text); ?>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <?php include 'includes/footer.php'; ?>

    <script>
        function toggleFAQ(btn) {
            const item = btn.closest('.faq-item');
            const isOpen = item.classList.toggle('open');
            btn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');

            // Accordion behavior: close any other open items in this section
            if (isOpen) {
                item.parentElement.querySelectorAll('.faq-item.open').forEach(function(other) {
                    if (other !== item) {
                        other.classList.remove('open');
                        other.querySelector('.faq-button').setAttribute('aria-expanded', 'false');
                    }
                });
            }
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
