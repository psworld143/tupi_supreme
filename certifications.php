<?php
require_once 'includes/config.php';
$current_page = 'certifications';

// Editable copy (managed via Admin -> Pages -> Certifications)
$page_header_title = getPageContent('certifications', 'page_header_title', 'Certifications & Quality Standards');
$page_header_subtitle = getPageContent('certifications', 'page_header_subtitle', 'Committed to quality, compliance, and industry standards');
$iso_title = getPageContent('certifications', 'iso_title', 'ISO Certifications');
$iso_subtitle = getPageContent('certifications', 'iso_subtitle', 'International quality management standards');
$product_certs_title = getPageContent('certifications', 'product_certs_title', 'Product Certifications');
$product_certs_subtitle = getPageContent('certifications', 'product_certs_subtitle', 'Industry standards and regulatory compliance for municipal water treatment');
$quality_title = getPageContent('certifications', 'quality_title', 'Quality Management Systems');
$quality_subtitle = getPageContent('certifications', 'quality_subtitle', 'Comprehensive quality assurance for municipal water treatment clients');
$compliance_title = getPageContent('certifications', 'compliance_title', 'Industry Compliance');
$compliance_subtitle = getPageContent('certifications', 'compliance_subtitle', 'Meeting and exceeding industry standards for activated carbon');
$testing_title = getPageContent('certifications', 'testing_title', 'Testing & Validation');
$testing_subtitle = getPageContent('certifications', 'testing_subtitle', 'Rigorous testing ensures consistent quality for municipal water treatment');
$testing_note = getPageContent('certifications', 'testing_note', 'All testing is performed in our certified laboratory with full documentation available upon request for municipal water treatment facilities.');
$cta_title = getPageContent('certifications', 'cta_title', 'Request Certification Documentation');
$cta_description = getPageContent('certifications', 'cta_description', 'Contact us to receive detailed certification documents, test reports, and compliance information for your municipal water treatment facility');
$cta_button_1_text = getPageContent('certifications', 'cta_button_1_text', 'Request Certifications');
$cta_button_1_link = getPageContent('certifications', 'cta_button_1_link', 'contact.php');
$cta_button_2_text = getPageContent('certifications', 'cta_button_2_text', 'Download Test Reports');
$cta_button_2_link = getPageContent('certifications', 'cta_button_2_link', 'resources.php');
$meta_description = getPageContent('certifications', 'meta_description', 'TSACI certifications and quality standards. ISO certifications, quality management systems, and industry compliance for activated carbon products.');

// Certification cards (managed via Admin -> Certifications)
$iso_certs = getCertifications('iso');
$product_certs = getCertifications('product');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo htmlspecialchars_safe($meta_description); ?>">
    <title>Certifications & Quality Standards | <?php echo htmlspecialchars_safe(getSiteSetting('company_short_name', 'TSACI')); ?></title>
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

        /* Cert cards */
        .cert-card {
            transition: transform 0.35s cubic-bezier(0.4, 0, 0.2, 1), border-color 0.35s ease, box-shadow 0.35s ease;
        }
        .cert-card:hover {
            transform: translateY(-6px);
            border-color: #3d7a66;
            box-shadow: 0 12px 32px -12px rgba(35, 51, 44, 0.15);
        }
        .cert-card:hover .cert-badge {
            transform: scale(1.08) rotate(-3deg);
        }
        .cert-badge {
            background: #3d7a66;
            transition: transform 0.35s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* Quality management rows */
        .quality-row {
            transition: background-color 0.3s ease;
        }
        .quality-row:hover {
            background-color: #f7faf8;
        }
        .quality-row:hover .cert-badge {
            transform: scale(1.08);
        }

        /* Compliance cards */
        .compliance-card {
            transition: transform 0.35s cubic-bezier(0.4, 0, 0.2, 1), border-color 0.35s ease;
        }
        .compliance-card:hover {
            transform: translateY(-4px);
            border-color: #3d7a66;
        }
        .compliance-card:hover .compliance-icon {
            transform: scale(1.08);
        }
        .compliance-icon {
            transition: transform 0.35s cubic-bezier(0.4, 0, 0.2, 1);
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
                    <i class="fas fa-certificate text-xs"></i> Quality & Compliance
                </span>
                <h1 class="text-4xl lg:text-6xl font-bold mb-5 leading-tight mt-4 fade-in fade-in-delay-2"><?php echo htmlspecialchars_safe($page_header_title); ?></h1>
                <p class="text-lg lg:text-xl text-white/85 max-w-2xl mx-auto fade-in fade-in-delay-3"><?php echo htmlspecialchars_safe($page_header_subtitle); ?></p>
            </div>
        </div>
    </section>

    <!-- ISO Certifications -->
    <?php if (!empty($iso_certs)): ?>
    <section id="iso-certifications" class="py-20 lg:py-24 relative overflow-hidden">
        <!-- Subtle dot grid backdrop -->
        <div class="absolute inset-0 dot-grid opacity-40"></div>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative">
            <div class="text-center mb-16 reveal">
                <span class="eyebrow mb-4">
                    <i class="fas fa-globe text-xs"></i> International Standards
                </span>
                <h2 class="text-3xl lg:text-4xl font-bold text-[#23332c] mb-4 mt-4"><?php echo htmlspecialchars_safe($iso_title); ?></h2>
                <p class="text-lg text-[#7d8b84] max-w-2xl mx-auto"><?php echo nl2br_safe($iso_subtitle); ?></p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <?php foreach ($iso_certs as $index => $cert):
                    $cert_features = !empty($cert['features']) ? array_filter(array_map('trim', explode('•', $cert['features']))) : [];
                ?>
                <div class="cert-card bg-white border border-[#e6ece8] rounded-2xl p-8 reveal reveal-delay-<?php echo ($index % 4) + 1; ?>">
                    <div class="flex items-start">
                        <div class="cert-badge w-16 h-16 rounded-2xl flex items-center justify-center mr-5 flex-shrink-0">
                            <i class="<?php echo htmlspecialchars_safe($cert['icon'] ?: 'fas fa-certificate'); ?> text-2xl text-white"></i>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-xl font-semibold text-[#23332c] mb-1"><?php echo htmlspecialchars_safe($cert['title']); ?></h3>
                            <?php if (!empty($cert['subtitle'])): ?>
                                <p class="text-[#3d7a66] text-sm font-medium mb-3"><?php echo htmlspecialchars_safe($cert['subtitle']); ?></p>
                            <?php endif; ?>
                            <?php if (!empty($cert['description'])): ?>
                                <p class="text-[#5a6b62] mb-4 leading-relaxed text-sm"><?php echo nl2br_safe($cert['description']); ?></p>
                            <?php endif; ?>
                            <?php if (!empty($cert_features)): ?>
                                <ul class="text-sm text-[#7d8b84] space-y-1.5">
                                    <?php foreach ($cert_features as $feature): ?>
                                        <li class="flex items-center"><i class="fas fa-check text-[#3d7a66] mr-2 text-xs"></i><?php echo htmlspecialchars_safe($feature); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Product Certifications -->
    <?php if (!empty($product_certs)): ?>
    <section id="product-certifications" class="bg-[#f5f7f5] py-20 lg:py-24">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16 reveal">
                <span class="eyebrow mb-4">
                    <i class="fas fa-award text-xs"></i> Product Compliance
                </span>
                <h2 class="text-3xl lg:text-4xl font-bold text-[#23332c] mb-4 mt-4"><?php echo htmlspecialchars_safe($product_certs_title); ?></h2>
                <p class="text-lg text-[#7d8b84] max-w-2xl mx-auto"><?php echo nl2br_safe($product_certs_subtitle); ?></p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <?php foreach ($product_certs as $index => $cert): ?>
                <div class="cert-card bg-white border border-[#e6ece8] rounded-2xl p-8 text-center reveal reveal-delay-<?php echo ($index % 4) + 1; ?>">
                    <div class="cert-badge w-20 h-20 rounded-2xl flex items-center justify-center mx-auto mb-6">
                        <i class="<?php echo htmlspecialchars_safe($cert['icon'] ?: 'fas fa-certificate'); ?> text-3xl text-white"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-[#23332c] mb-3"><?php echo htmlspecialchars_safe($cert['title']); ?></h3>
                    <?php if (!empty($cert['description'])): ?>
                        <p class="text-[#7d8b84] mb-4 leading-relaxed text-sm"><?php echo nl2br_safe($cert['description']); ?></p>
                    <?php endif; ?>
                    <?php if (!empty($cert['badge_label'])): ?>
                        <span class="eyebrow"><?php echo htmlspecialchars_safe($cert['badge_label']); ?></span>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Quality Management -->
    <section id="quality-management" class="py-20 lg:py-24 relative overflow-hidden">
        <div class="absolute inset-0 dot-grid opacity-40"></div>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative">
            <div class="text-center mb-16 reveal">
                <span class="eyebrow mb-4">
                    <i class="fas fa-clipboard-check text-xs"></i> QA Systems
                </span>
                <h2 class="text-3xl lg:text-4xl font-bold text-[#23332c] mb-4 mt-4"><?php echo htmlspecialchars_safe($quality_title); ?></h2>
                <p class="text-lg text-[#7d8b84] max-w-2xl mx-auto"><?php echo nl2br_safe($quality_subtitle); ?></p>
            </div>
            <div class="max-w-4xl mx-auto reveal">
                <div class="bg-white border border-[#e6ece8] rounded-2xl overflow-hidden divide-y divide-[#e6ece8]">
                    <div class="quality-row flex items-center p-6">
                        <div class="cert-badge w-12 h-12 rounded-2xl flex items-center justify-center mr-5 flex-shrink-0">
                            <i class="fas fa-microscope text-lg text-white"></i>
                        </div>
                        <div>
                            <h5 class="text-lg font-semibold text-[#23332c] mb-1">Laboratory Testing</h5>
                            <p class="text-[#7d8b84] text-sm leading-relaxed">Comprehensive testing of every batch including particle size analysis (2mm specification), surface area, iodine number, and quality parameters.</p>
                        </div>
                    </div>
                    <div class="quality-row flex items-center p-6">
                        <div class="cert-badge w-12 h-12 rounded-2xl flex items-center justify-center mr-5 flex-shrink-0">
                            <i class="fas fa-clipboard-check text-lg text-white"></i>
                        </div>
                        <div>
                            <h5 class="text-lg font-semibold text-[#23332c] mb-1">Batch Documentation</h5>
                            <p class="text-[#7d8b84] text-sm leading-relaxed">Complete documentation for every production batch including test results, specifications, and traceability for municipal water treatment compliance.</p>
                        </div>
                    </div>
                    <div class="quality-row flex items-center p-6">
                        <div class="cert-badge w-12 h-12 rounded-2xl flex items-center justify-center mr-5 flex-shrink-0">
                            <i class="fas fa-chart-line text-lg text-white"></i>
                        </div>
                        <div>
                            <h5 class="text-lg font-semibold text-[#23332c] mb-1">Performance Monitoring</h5>
                            <p class="text-[#7d8b84] text-sm leading-relaxed">Continuous monitoring and quality control processes ensure consistent performance of our 2mm granulated activated carbon for municipal applications.</p>
                        </div>
                    </div>
                    <div class="quality-row flex items-center p-6">
                        <div class="cert-badge w-12 h-12 rounded-2xl flex items-center justify-center mr-5 flex-shrink-0">
                            <i class="fas fa-certificate text-lg text-white"></i>
                        </div>
                        <div>
                            <h5 class="text-lg font-semibold text-[#23332c] mb-1">Compliance Verification</h5>
                            <p class="text-[#7d8b84] text-sm leading-relaxed">Regular audits and compliance verification to ensure all products meet municipal water treatment facility requirements and regulatory standards.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Industry Compliance -->
    <section id="industry-compliance" class="bg-[#23332c] text-white py-20 lg:py-24 relative overflow-hidden">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative">
            <div class="text-center mb-16 reveal">
                <span class="eyebrow bg-white/15 text-white/90 mb-4">
                    <i class="fas fa-balance-scale text-xs"></i> Compliance
                </span>
                <h2 class="text-3xl lg:text-4xl font-bold mb-4 mt-4"><?php echo htmlspecialchars_safe($compliance_title); ?></h2>
                <p class="text-lg text-white/70 max-w-2xl mx-auto"><?php echo nl2br_safe($compliance_subtitle); ?></p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5">
                <div class="compliance-card bg-white/5 backdrop-blur-sm border border-white/10 rounded-2xl p-6 text-center reveal reveal-delay-1">
                    <div class="compliance-icon w-14 h-14 rounded-2xl flex items-center justify-center mx-auto mb-4 bg-[#3d7a66]">
                        <i class="fas fa-check-circle text-2xl text-white"></i>
                    </div>
                    <h4 class="text-base font-semibold mb-2">EPA Standards</h4>
                    <p class="text-white/70 text-sm">Environmental Protection Agency compliance</p>
                </div>
                <div class="compliance-card bg-white/5 backdrop-blur-sm border border-white/10 rounded-2xl p-6 text-center reveal reveal-delay-2">
                    <div class="compliance-icon w-14 h-14 rounded-2xl flex items-center justify-center mx-auto mb-4 bg-[#3d7a66]">
                        <i class="fas fa-check-circle text-2xl text-white"></i>
                    </div>
                    <h4 class="text-base font-semibold mb-2">WHO Guidelines</h4>
                    <p class="text-white/70 text-sm">World Health Organization drinking water standards</p>
                </div>
                <div class="compliance-card bg-white/5 backdrop-blur-sm border border-white/10 rounded-2xl p-6 text-center reveal reveal-delay-3">
                    <div class="compliance-icon w-14 h-14 rounded-2xl flex items-center justify-center mx-auto mb-4 bg-[#3d7a66]">
                        <i class="fas fa-check-circle text-2xl text-white"></i>
                    </div>
                    <h4 class="text-base font-semibold mb-2">ANSI Standards</h4>
                    <p class="text-white/70 text-sm">American National Standards Institute compliance</p>
                </div>
                <div class="compliance-card bg-white/5 backdrop-blur-sm border border-white/10 rounded-2xl p-6 text-center reveal reveal-delay-4">
                    <div class="compliance-icon w-14 h-14 rounded-2xl flex items-center justify-center mx-auto mb-4 bg-[#3d7a66]">
                        <i class="fas fa-check-circle text-2xl text-white"></i>
                    </div>
                    <h4 class="text-base font-semibold mb-2">ASTM Standards</h4>
                    <p class="text-white/70 text-sm">American Society for Testing and Materials</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Testing & Validation -->
    <section id="testing-validation" class="py-20 lg:py-24">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16 reveal">
                <span class="eyebrow mb-4">
                    <i class="fas fa-vial text-xs"></i> Lab Results
                </span>
                <h2 class="text-3xl lg:text-4xl font-bold text-[#23332c] mb-4 mt-4"><?php echo htmlspecialchars_safe($testing_title); ?></h2>
                <p class="text-lg text-[#7d8b84] max-w-2xl mx-auto"><?php echo nl2br_safe($testing_subtitle); ?></p>
            </div>
            <div class="max-w-6xl mx-auto reveal">
                <div class="bg-white border border-[#e6ece8] rounded-2xl overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[640px] specs-table">
                        <thead>
                            <tr>
                                <th class="px-6 py-4 text-left font-semibold">Test Parameter</th>
                                <th class="px-6 py-4 text-center font-semibold">Specification</th>
                                <th class="px-6 py-4 text-center font-semibold">Standard</th>
                                <th class="px-6 py-4 text-center font-semibold">Frequency</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#e6ece8]">
                            <tr>
                                <td class="px-6 py-4 font-medium text-[#23332c]">Particle Size</td>
                                <td class="px-6 py-4 text-center text-[#3d7a66] font-medium">2mm below (granulated)</td>
                                <td class="px-6 py-4 text-center text-[#5a6b62]">ASTM D2862</td>
                                <td class="px-6 py-4 text-center text-[#5a6b62]">Every batch</td>
                            </tr>
                            <tr>
                                <td class="px-6 py-4 font-medium text-[#23332c]">Surface Area</td>
                                <td class="px-6 py-4 text-center text-[#5a6b62]">800-1500 m²/g</td>
                                <td class="px-6 py-4 text-center text-[#5a6b62]">BET Method</td>
                                <td class="px-6 py-4 text-center text-[#5a6b62]">Every batch</td>
                            </tr>
                            <tr>
                                <td class="px-6 py-4 font-medium text-[#23332c]">Iodine Number</td>
                                <td class="px-6 py-4 text-center text-[#5a6b62]">800-1400</td>
                                <td class="px-6 py-4 text-center text-[#5a6b62]">ASTM D4607</td>
                                <td class="px-6 py-4 text-center text-[#5a6b62]">Every batch</td>
                            </tr>
                            <tr>
                                <td class="px-6 py-4 font-medium text-[#23332c]">Ash Content</td>
                                <td class="px-6 py-4 text-center text-[#5a6b62]">≤ 2-8%</td>
                                <td class="px-6 py-4 text-center text-[#5a6b62]">ASTM D2866</td>
                                <td class="px-6 py-4 text-center text-[#5a6b62]">Every batch</td>
                            </tr>
                            <tr>
                                <td class="px-6 py-4 font-medium text-[#23332c]">Moisture Content</td>
                                <td class="px-6 py-4 text-center text-[#5a6b62]">≤ 1-5%</td>
                                <td class="px-6 py-4 text-center text-[#5a6b62]">ASTM D2867</td>
                                <td class="px-6 py-4 text-center text-[#5a6b62]">Every batch</td>
                            </tr>
                            <tr>
                                <td class="px-6 py-4 font-medium text-[#23332c]">Bulk Density</td>
                                <td class="px-6 py-4 text-center text-[#5a6b62]">400-600 g/L</td>
                                <td class="px-6 py-4 text-center text-[#5a6b62]">ASTM D2854</td>
                                <td class="px-6 py-4 text-center text-[#5a6b62]">Every batch</td>
                            </tr>
                        </tbody>
                    </table>
                    </div>
                </div>
                <p class="text-center text-[#7d8b84] mt-6 text-sm"><?php echo nl2br_safe($testing_note); ?></p>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section id="cta" class="relative overflow-hidden py-20 lg:py-24">
        <div class="absolute inset-0 page-header-gradient"></div>
        <div class="page-header-pattern absolute inset-0 opacity-30"></div>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative text-center text-white reveal">
            <span class="eyebrow bg-white/15 text-white/90 mb-5">
                <i class="fas fa-file-alt text-xs"></i> Documentation
            </span>
            <h2 class="text-3xl lg:text-4xl font-bold mb-4 mt-4"><?php echo htmlspecialchars_safe($cta_title); ?></h2>
            <p class="text-lg mb-8 text-white/85 max-w-2xl mx-auto"><?php echo nl2br_safe($cta_description); ?></p>
            <div class="flex flex-col sm:flex-row justify-center gap-3">
                <a href="<?php echo htmlspecialchars_safe($cta_button_1_link); ?>" class="bg-white text-[#23332c] hover:bg-[#eff4f1] font-medium py-3 px-8 rounded-full transition-colors inline-flex items-center justify-center gap-2">
                    <?php echo htmlspecialchars_safe($cta_button_1_text); ?>
                    <i class="fas fa-arrow-right text-xs"></i>
                </a>
                <a href="<?php echo htmlspecialchars_safe($cta_button_2_link); ?>" class="border-2 border-white/80 text-white hover:bg-white hover:text-[#23332c] font-medium py-3 px-8 rounded-full transition-colors inline-flex items-center justify-center gap-2">
                    <i class="fas fa-download text-xs"></i>
                    <?php echo htmlspecialchars_safe($cta_button_2_text); ?>
                </a>
            </div>
        </div>
    </section>

    <?php include 'includes/footer.php'; ?>

    <!-- Scroll-triggered reveal animations -->
    <script>
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
