<?php
require_once 'includes/config.php';
$current_page = 'certifications';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="TSACI certifications and quality standards. ISO certifications, quality management systems, and industry compliance for activated carbon products.">
    <title>Certifications & Quality Standards | <?php echo htmlspecialchars_safe(getSiteSetting('company_short_name', 'TSACI')); ?></title>
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
        
        .cert-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .cert-card:hover {
            transform: translateY(-5px);
        }
        
        .cert-badge {
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
                <h1 class="text-5xl lg:text-6xl font-bold mb-6">Certifications & Quality Standards</h1>
                <p class="text-xl">Committed to quality, compliance, and industry standards</p>
            </div>
        </div>
    </section>

    <!-- ISO Certifications -->
    <section class="py-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-4xl lg:text-5xl font-bold text-gray-900 mb-4">ISO Certifications</h2>
                <p class="text-xl text-gray-600">International quality management standards</p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div class="cert-card bg-white rounded-2xl shadow-lg p-8">
                    <div class="flex items-start">
                        <div class="cert-badge w-20 h-20 rounded-full flex items-center justify-center mr-6 flex-shrink-0">
                            <i class="fas fa-certificate text-3xl text-white"></i>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-2xl font-bold text-gray-900 mb-3">ISO 9001:2015</h3>
                            <p class="text-gray-600 mb-4">Quality Management Systems</p>
                            <p class="text-gray-600 mb-4">Certified since 2010, demonstrating our commitment to consistent quality management and continuous improvement in all our operations.</p>
                            <ul class="text-sm text-gray-500 space-y-1">
                                <li>• Quality management system certification</li>
                                <li>• Process standardization</li>
                                <li>• Continuous improvement framework</li>
                                <li>• Customer satisfaction focus</li>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <div class="cert-card bg-white rounded-2xl shadow-lg p-8">
                    <div class="flex items-start">
                        <div class="cert-badge w-20 h-20 rounded-full flex items-center justify-center mr-6 flex-shrink-0">
                            <i class="fas fa-shield-alt text-3xl text-white"></i>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-2xl font-bold text-gray-900 mb-3">ISO 14001:2015</h3>
                            <p class="text-gray-600 mb-4">Environmental Management Systems</p>
                            <p class="text-gray-600 mb-4">Certification demonstrating our commitment to environmental responsibility and sustainable operations in activated carbon production.</p>
                            <ul class="text-sm text-gray-500 space-y-1">
                                <li>• Environmental management system</li>
                                <li>• Zero-waste operations</li>
                                <li>• Sustainable resource utilization</li>
                                <li>• Environmental compliance</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Product Certifications -->
    <section class="bg-light py-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-4xl lg:text-5xl font-bold text-gray-900 mb-4">Product Certifications</h2>
                <p class="text-xl text-gray-600">Industry standards and regulatory compliance for municipal water treatment</p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="cert-card bg-white rounded-2xl shadow-lg p-8 text-center">
                    <div class="cert-badge w-24 h-24 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-check-circle text-4xl text-white"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">NSF/ANSI Standards</h3>
                    <p class="text-gray-600 mb-4">Certified for drinking water treatment applications. Our activated carbon products meet NSF/ANSI Standard 61 for drinking water system components.</p>
                    <p class="text-sm text-gray-500">Compliant for municipal water treatment use</p>
                </div>
                
                <div class="cert-card bg-white rounded-2xl shadow-lg p-8 text-center">
                    <div class="cert-badge w-24 h-24 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-award text-4xl text-white"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Water Quality Standards</h3>
                    <p class="text-gray-600 mb-4">Compliance with EPA and WHO drinking water quality standards. Our 2mm granulated activated carbon meets all regulatory requirements for municipal applications.</p>
                    <p class="text-sm text-gray-500">Regulatory compliant</p>
                </div>
                
                <div class="cert-card bg-white rounded-2xl shadow-lg p-8 text-center">
                    <div class="cert-badge w-24 h-24 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-flask text-4xl text-white"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Quality Control Testing</h3>
                    <p class="text-gray-600 mb-4">Rigorous batch testing and quality control processes ensure consistent product specifications, including our strict 2mm granulated particle size standard.</p>
                    <p class="text-sm text-gray-500">Batch-to-batch consistency</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Quality Management -->
    <section class="py-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-4xl lg:text-5xl font-bold text-gray-900 mb-4">Quality Management Systems</h2>
                <p class="text-xl text-gray-600">Comprehensive quality assurance for municipal water treatment clients</p>
            </div>
            <div class="max-w-4xl mx-auto">
                <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
                    <div class="flex items-center p-6 border-b border-gray-200">
                        <div class="cert-badge w-12 h-12 rounded-full flex items-center justify-center mr-6">
                            <i class="fas fa-microscope text-xl text-white"></i>
                        </div>
                        <div>
                            <h5 class="text-xl font-bold text-gray-900 mb-2">Laboratory Testing</h5>
                            <p class="text-gray-600">Comprehensive testing of every batch including particle size analysis (2mm specification), surface area, iodine number, and quality parameters.</p>
                        </div>
                    </div>
                    <div class="flex items-center p-6 border-b border-gray-200">
                        <div class="cert-badge w-12 h-12 rounded-full flex items-center justify-center mr-6">
                            <i class="fas fa-clipboard-check text-xl text-white"></i>
                        </div>
                        <div>
                            <h5 class="text-xl font-bold text-gray-900 mb-2">Batch Documentation</h5>
                            <p class="text-gray-600">Complete documentation for every production batch including test results, specifications, and traceability for municipal water treatment compliance.</p>
                        </div>
                    </div>
                    <div class="flex items-center p-6 border-b border-gray-200">
                        <div class="cert-badge w-12 h-12 rounded-full flex items-center justify-center mr-6">
                            <i class="fas fa-chart-line text-xl text-white"></i>
                        </div>
                        <div>
                            <h5 class="text-xl font-bold text-gray-900 mb-2">Performance Monitoring</h5>
                            <p class="text-gray-600">Continuous monitoring and quality control processes ensure consistent performance of our 2mm granulated activated carbon for municipal applications.</p>
                        </div>
                    </div>
                    <div class="flex items-center p-6">
                        <div class="cert-badge w-12 h-12 rounded-full flex items-center justify-center mr-6">
                            <i class="fas fa-certificate text-xl text-white"></i>
                        </div>
                        <div>
                            <h5 class="text-xl font-bold text-gray-900 mb-2">Compliance Verification</h5>
                            <p class="text-gray-600">Regular audits and compliance verification to ensure all products meet municipal water treatment facility requirements and regulatory standards.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Industry Compliance -->
    <section class="bg-light py-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-4xl lg:text-5xl font-bold text-gray-900 mb-4">Industry Compliance</h2>
                <p class="text-xl text-gray-600">Meeting and exceeding industry standards for activated carbon</p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <div class="bg-white rounded-lg shadow-lg p-6 text-center">
                    <i class="fas fa-check-circle text-4xl text-primary mb-4"></i>
                    <h4 class="text-lg font-bold text-gray-900 mb-2">EPA Standards</h4>
                    <p class="text-gray-600 text-sm">Environmental Protection Agency compliance</p>
                </div>
                <div class="bg-white rounded-lg shadow-lg p-6 text-center">
                    <i class="fas fa-check-circle text-4xl text-primary mb-4"></i>
                    <h4 class="text-lg font-bold text-gray-900 mb-2">WHO Guidelines</h4>
                    <p class="text-gray-600 text-sm">World Health Organization drinking water standards</p>
                </div>
                <div class="bg-white rounded-lg shadow-lg p-6 text-center">
                    <i class="fas fa-check-circle text-4xl text-primary mb-4"></i>
                    <h4 class="text-lg font-bold text-gray-900 mb-2">ANSI Standards</h4>
                    <p class="text-gray-600 text-sm">American National Standards Institute compliance</p>
                </div>
                <div class="bg-white rounded-lg shadow-lg p-6 text-center">
                    <i class="fas fa-check-circle text-4xl text-primary mb-4"></i>
                    <h4 class="text-lg font-bold text-gray-900 mb-2">ASTM Standards</h4>
                    <p class="text-gray-600 text-sm">American Society for Testing and Materials</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Testing & Validation -->
    <section class="py-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-4xl lg:text-5xl font-bold text-gray-900 mb-4">Testing & Validation</h2>
                <p class="text-xl text-gray-600">Rigorous testing ensures consistent quality for municipal water treatment</p>
            </div>
            <div class="max-w-6xl mx-auto">
                <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[640px]">
                        <thead>
                            <tr class="bg-primary text-white">
                                <th class="px-6 py-4 text-left font-semibold">Test Parameter</th>
                                <th class="px-6 py-4 text-center font-semibold">Specification</th>
                                <th class="px-6 py-4 text-center font-semibold">Standard</th>
                                <th class="px-6 py-4 text-center font-semibold">Frequency</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 font-semibold">Particle Size</td>
                                <td class="px-6 py-4 text-center">2mm below (granulated)</td>
                                <td class="px-6 py-4 text-center">ASTM D2862</td>
                                <td class="px-6 py-4 text-center">Every batch</td>
                            </tr>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 font-semibold">Surface Area</td>
                                <td class="px-6 py-4 text-center">800-1500 m²/g</td>
                                <td class="px-6 py-4 text-center">BET Method</td>
                                <td class="px-6 py-4 text-center">Every batch</td>
                            </tr>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 font-semibold">Iodine Number</td>
                                <td class="px-6 py-4 text-center">800-1400</td>
                                <td class="px-6 py-4 text-center">ASTM D4607</td>
                                <td class="px-6 py-4 text-center">Every batch</td>
                            </tr>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 font-semibold">Ash Content</td>
                                <td class="px-6 py-4 text-center">≤ 2-8%</td>
                                <td class="px-6 py-4 text-center">ASTM D2866</td>
                                <td class="px-6 py-4 text-center">Every batch</td>
                            </tr>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 font-semibold">Moisture Content</td>
                                <td class="px-6 py-4 text-center">≤ 1-5%</td>
                                <td class="px-6 py-4 text-center">ASTM D2867</td>
                                <td class="px-6 py-4 text-center">Every batch</td>
                            </tr>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 font-semibold">Bulk Density</td>
                                <td class="px-6 py-4 text-center">400-600 g/L</td>
                                <td class="px-6 py-4 text-center">ASTM D2854</td>
                                <td class="px-6 py-4 text-center">Every batch</td>
                            </tr>
                        </tbody>
                    </table>
                    </div>
                </div>
                <p class="text-center text-gray-600 mt-6">All testing is performed in our certified laboratory with full documentation available upon request for municipal water treatment facilities.</p>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="bg-primary text-white py-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-4xl lg:text-5xl font-bold mb-6">Request Certification Documentation</h2>
            <p class="text-xl mb-8">Contact us to receive detailed certification documents, test reports, and compliance information for your municipal water treatment facility</p>
            <div class="flex flex-col sm:flex-row justify-center gap-4">
                <a href="contact.php" class="bg-white text-primary hover:bg-gray-100 font-bold py-3 px-8 rounded-lg transition duration-300 inline-block">Request Certifications</a>
                <a href="resources.php" class="border-2 border-white text-white hover:bg-white hover:text-primary font-bold py-3 px-8 rounded-lg transition duration-300 inline-block">Download Test Reports</a>
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

