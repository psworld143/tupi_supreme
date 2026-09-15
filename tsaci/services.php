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

// Static process steps (kept inline in the original)
$process_steps = [
    ['fa-search',      'Assessment',       'We begin by thoroughly assessing your specific needs, requirements, and application challenges.'],
    ['fa-lightbulb',   'Solution Design',  'Our experts design customized solutions tailored to your specific application and requirements.'],
    ['fa-cogs',        'Implementation',   'We implement the solution with precision, ensuring optimal performance and reliability.'],
    ['fa-chart-line',  'Monitoring',       'Continuous monitoring and support to ensure long-term success and optimal performance.'],
];

$service_features = [
    ['fa-award',       'Expert Team',              'Our team consists of certified professionals with decades of experience in activated carbon applications.'],
    ['fa-clock',       '24/7 Support',             'Round-the-clock technical support and emergency services to ensure your operations never stop.'],
    ['fa-shield-alt',  'Quality Assurance',         'Rigorous quality control processes ensure consistent, reliable service delivery every time.'],
    ['fa-leaf',        'Sustainable Solutions',     'Environmentally responsible service practices that align with your sustainability goals.'],
    ['fa-handshake',   'Long-term Partnership',     'We build lasting relationships with our clients, providing ongoing support and value.'],
    ['fa-chart-bar',   'Performance Optimization',  'Continuous improvement services to maximize efficiency and reduce operational costs.'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Services - <?php echo htmlspecialchars_safe(getSiteSetting('company_name', 'Tupi Supreme Activated Carbon, Inc.')); ?></title>
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

        /* Service cards */
        .service-card {
            transition: transform 0.35s cubic-bezier(0.4, 0, 0.2, 1), border-color 0.35s ease, box-shadow 0.35s ease;
        }
        .service-card:hover {
            transform: translateY(-6px);
            border-color: #3d7a66;
            box-shadow: 0 12px 32px -12px rgba(35, 51, 44, 0.15);
        }
        .service-card:hover .service-icon {
            transform: scale(1.08) rotate(-3deg);
        }
        .service-icon {
            background: linear-gradient(135deg, #3d7a66, #60796e);
            transition: transform 0.35s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* Process steps */
        .process-step {
            position: relative;
        }
        .process-step::after {
            content: '';
            position: absolute;
            top: 2.5rem;
            right: -25%;
            width: 50%;
            height: 2px;
            background: linear-gradient(90deg, #c0ccc5, #e6ece8);
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
            background: linear-gradient(135deg, #23332c, #3d7a66);
            transition: transform 0.35s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .process-step:hover .process-icon {
            transform: scale(1.08);
        }
        .process-number {
            position: absolute;
            top: -0.5rem;
            right: -0.5rem;
            width: 1.75rem;
            height: 1.75rem;
            border-radius: 9999px;
            background-color: #8bc34a;
            color: #23332c;
            font-size: 0.75rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid #f5f7f5;
        }

        /* Feature list rows */
        .feature-row {
            transition: background-color 0.3s ease;
        }
        .feature-row:hover {
            background-color: #f7faf8;
        }
        .feature-row:hover .feature-icon {
            transform: scale(1.08);
        }
        .feature-icon {
            background: linear-gradient(135deg, #3d7a66, #60796e);
            transition: transform 0.35s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* Testimonial cards */
        .testimonial-card {
            transition: transform 0.35s cubic-bezier(0.4, 0, 0.2, 1), border-color 0.35s ease, box-shadow 0.35s ease;
        }
        .testimonial-card:hover {
            transform: translateY(-6px);
            border-color: #3d7a66;
            box-shadow: 0 12px 32px -12px rgba(35, 51, 44, 0.15);
        }
        .testimonial-avatar {
            background: linear-gradient(135deg, #3d7a66, #60796e);
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
    <section class="page-header-gradient text-white relative overflow-hidden pt-24 pb-20">
        <!-- Floating gradient orbs for depth -->
        <div class="orb orb-1"></div>
        <div class="orb orb-2"></div>
        <div class="page-header-pattern absolute inset-0 opacity-30"></div>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="text-center">
                <span class="eyebrow bg-white/15 text-white/90 mb-5 fade-in fade-in-delay-1">
                    <i class="fas fa-concierge-bell text-xs"></i> What We Offer
                </span>
                <h1 class="text-4xl lg:text-6xl font-bold mb-5 leading-tight mt-4 fade-in fade-in-delay-2"><?php echo htmlspecialchars_safe($page_header_title); ?></h1>
                <p class="text-lg lg:text-xl text-white/85 max-w-2xl mx-auto fade-in fade-in-delay-3"><?php echo htmlspecialchars_safe($page_header_subtitle); ?></p>
            </div>
        </div>
    </section>

    <!-- Main Services -->
    <section class="py-20 lg:py-24 relative overflow-hidden">
        <!-- Subtle dot grid backdrop -->
        <div class="absolute inset-0 dot-grid opacity-40"></div>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative">
            <div class="text-center mb-16 reveal">
                <span class="eyebrow mb-4">
                    <i class="fas fa-th-large text-xs"></i> Services
                </span>
                <h2 class="text-3xl lg:text-4xl font-bold text-[#23332c] mb-4 mt-4"><?php echo htmlspecialchars_safe($section_title); ?></h2>
                <p class="text-lg text-[#7d8b84] max-w-2xl mx-auto"><?php echo htmlspecialchars_safe($section_subtitle); ?></p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($services as $i => $service): ?>
                    <div class="service-card bg-white border border-[#e6ece8] rounded-2xl p-8 h-full reveal <?php echo 'reveal-delay-' . ((($i % 3) + 1)); ?>">
                        <div class="text-center">
                            <?php if ($service['icon']): ?>
                                <div class="service-icon w-20 h-20 rounded-2xl flex items-center justify-center mx-auto mb-6">
                                    <i class="<?php echo htmlspecialchars_safe($service['icon']); ?> text-3xl text-white"></i>
                                </div>
                            <?php endif; ?>
                            <h4 class="text-xl font-semibold text-[#23332c] mb-3"><?php echo htmlspecialchars_safe($service['title']); ?></h4>
                            <?php if ($service['description']): ?>
                                <p class="text-[#7d8b84] mb-6 leading-relaxed text-sm"><?php echo htmlspecialchars_safe($service['description']); ?></p>
                            <?php endif; ?>
                            <?php if ($service['features']): ?>
                                <ul class="text-left space-y-2.5">
                                    <?php 
                                    $features = explode("\n", $service['features']);
                                    foreach ($features as $feature):
                                        $feature = trim($feature);
                                        if ($feature):
                                    ?>
                                        <li class="flex items-start text-[#5a6b62] text-sm"><i class="fas fa-check text-[#3d7a66] mr-3 mt-1"></i><?php echo htmlspecialchars_safe($feature); ?></li>
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
    <section class="bg-[#23332c] text-white py-20 lg:py-24 relative overflow-hidden">
        <!-- Decorative orbs -->
        <div class="orb orb-1" style="background: #3d7a66; opacity: 0.25;"></div>
        <div class="orb orb-2" style="background: #8bc34a; opacity: 0.2;"></div>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative">
            <div class="text-center mb-16 reveal">
                <span class="eyebrow bg-white/15 text-white/90 mb-4">
                    <i class="fas fa-stream text-xs"></i> How We Work
                </span>
                <h2 class="text-3xl lg:text-4xl font-bold mb-4 mt-4"><?php echo htmlspecialchars_safe($process_title); ?></h2>
                <p class="text-lg text-white/70 max-w-2xl mx-auto"><?php echo htmlspecialchars_safe($process_subtitle); ?></p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                <?php foreach ($process_steps as $i => $step): ?>
                    <div class="process-step text-center p-6 reveal <?php echo 'reveal-delay-' . (($i + 1)); ?>">
                        <div class="process-icon w-20 h-20 rounded-2xl flex items-center justify-center mx-auto mb-5 relative z-20">
                            <i class="<?php echo $step[0]; ?> text-2xl text-white"></i>
                            <span class="process-number"><?php echo $i + 1; ?></span>
                        </div>
                        <h5 class="text-lg font-semibold mb-2 relative z-10"><?php echo htmlspecialchars_safe($step[1]); ?></h5>
                        <p class="text-white/70 text-sm leading-relaxed relative z-10"><?php echo htmlspecialchars_safe($step[2]); ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Service Features -->
    <section class="py-20 lg:py-24">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16 reveal">
                <span class="eyebrow mb-4">
                    <i class="fas fa-star text-xs"></i> Why Us
                </span>
                <h2 class="text-3xl lg:text-4xl font-bold text-[#23332c] mb-4 mt-4"><?php echo htmlspecialchars_safe($features_title); ?></h2>
                <p class="text-lg text-[#7d8b84] max-w-2xl mx-auto"><?php echo htmlspecialchars_safe($features_subtitle); ?></p>
            </div>
            <div class="max-w-4xl mx-auto reveal">
                <div class="bg-white border border-[#e6ece8] rounded-2xl overflow-hidden divide-y divide-[#e6ece8]">
                    <?php foreach ($service_features as $i => $feat): ?>
                        <div class="feature-row flex items-center p-6 <?php echo $i < count($service_features) - 1 ? '' : ''; ?>">
                            <div class="feature-icon w-12 h-12 rounded-2xl flex items-center justify-center mr-5 flex-shrink-0">
                                <i class="<?php echo $feat[0]; ?> text-lg text-white"></i>
                            </div>
                            <div>
                                <h5 class="text-lg font-semibold text-[#23332c] mb-1"><?php echo htmlspecialchars_safe($feat[1]); ?></h5>
                                <p class="text-[#7d8b84] text-sm leading-relaxed"><?php echo htmlspecialchars_safe($feat[2]); ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- Testimonials -->
    <?php if (!empty($testimonials)): ?>
    <section class="bg-[#f5f7f5] py-20 lg:py-24">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16 reveal">
                <span class="eyebrow mb-4">
                    <i class="fas fa-quote-right text-xs"></i> Testimonials
                </span>
                <h2 class="text-3xl lg:text-4xl font-bold text-[#23332c] mb-4 mt-4"><?php echo htmlspecialchars_safe($testimonials_title); ?></h2>
                <p class="text-lg text-[#7d8b84] max-w-2xl mx-auto"><?php echo htmlspecialchars_safe($testimonials_subtitle); ?></p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <?php foreach ($testimonials as $i => $testimonial): ?>
                    <div class="testimonial-card bg-white border border-[#e6ece8] rounded-2xl p-8 reveal <?php echo 'reveal-delay-' . ((($i % 3) + 1)); ?>">
                        <div class="text-center mb-6">
                            <?php if ($testimonial['photo_url']): ?>
                                <img src="<?php echo htmlspecialchars_safe($testimonial['photo_url']); ?>" alt="<?php echo htmlspecialchars_safe($testimonial['name']); ?>" class="w-16 h-16 rounded-full mx-auto mb-4 object-cover border-4 border-[#eef3f0]">
                            <?php else: ?>
                                <div class="testimonial-avatar w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4 border-4 border-[#eef3f0]">
                                    <i class="fas fa-user text-2xl text-white"></i>
                                </div>
                            <?php endif; ?>
                            <h5 class="text-lg font-semibold text-[#23332c] mb-1"><?php echo htmlspecialchars_safe($testimonial['name']); ?></h5>
                            <?php if ($testimonial['position']): ?>
                                <p class="text-[#3d7a66] text-sm font-medium"><?php echo htmlspecialchars_safe($testimonial['position']); ?></p>
                            <?php endif; ?>
                            <?php if ($testimonial['company']): ?>
                                <p class="text-[#8a978f] text-xs mt-1"><?php echo htmlspecialchars_safe($testimonial['company']); ?></p>
                            <?php endif; ?>
                        </div>
                        <?php if ($testimonial['testimonial']): ?>
                            <p class="text-center text-[#5a6b62] leading-relaxed text-sm">"<?php echo htmlspecialchars_safe($testimonial['testimonial']); ?>"</p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- CTA Section -->
    <section class="relative overflow-hidden py-20 lg:py-24">
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
                        <i class="fas fa-file-invoice-dollar text-xs"></i>
                        <?php echo htmlspecialchars_safe($cta_button_2_text); ?>
                    </a>
                <?php endif; ?>
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
