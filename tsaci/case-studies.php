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

        /* Case study cards */
        .case-study-card {
            transition: transform 0.35s cubic-bezier(0.4, 0, 0.2, 1), border-color 0.35s ease, box-shadow 0.35s ease;
        }
        .case-study-card:hover {
            transform: translateY(-6px);
            border-color: #3d7a66;
            box-shadow: 0 12px 32px -12px rgba(35, 51, 44, 0.15);
        }

        /* Result metric stat cards */
        .result-stat {
            transition: transform 0.35s cubic-bezier(0.4, 0, 0.2, 1), border-color 0.35s ease;
        }
        .result-stat:hover {
            transform: translateY(-4px);
            border-color: #3d7a66;
        }

        /* Project details panel */
        .details-panel {
            background-color: #f5f7f5;
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
                    <i class="fas fa-book text-xs"></i> Success Stories
                </span>
                <h1 class="text-4xl lg:text-6xl font-bold mb-5 leading-tight mt-4 fade-in fade-in-delay-2"><?php echo htmlspecialchars_safe($page_header_title); ?></h1>
                <p class="text-lg lg:text-xl text-white/85 max-w-2xl mx-auto fade-in fade-in-delay-3"><?php echo htmlspecialchars_safe($page_header_subtitle); ?></p>
            </div>
        </div>
    </section>

    <!-- Featured Case Studies -->
    <section class="py-20 lg:py-24 relative overflow-hidden">
        <!-- Subtle dot grid backdrop -->
        <div class="absolute inset-0 dot-grid opacity-40"></div>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative">
            <div class="text-center mb-16 reveal">
                <span class="eyebrow mb-4">
                    <i class="fas fa-trophy text-xs"></i> Proven Results
                </span>
                <h2 class="text-3xl lg:text-4xl font-bold text-[#23332c] mb-4 mt-4"><?php echo htmlspecialchars_safe($section_title); ?></h2>
                <p class="text-lg text-[#7d8b84] max-w-2xl mx-auto"><?php echo htmlspecialchars_safe($section_subtitle); ?></p>
            </div>

            <?php if (!empty($case_studies)): ?>
                <?php foreach ($case_studies as $i => $case): ?>
                    <div class="case-study-card bg-white border border-[#e6ece8] rounded-2xl overflow-hidden mb-8 reveal">
                        <div class="lg:flex">
                            <div class="lg:w-2/3 p-8 lg:p-10">
                                <div class="flex items-center gap-3 mb-5">
                                    <?php if ($case['is_featured']): ?>
                                        <span class="eyebrow">
                                            <i class="fas fa-star text-xs"></i> Featured
                                        </span>
                                    <?php endif; ?>
                                    <?php if ($case['industry']): ?>
                                        <span class="text-[#8a978f] text-sm font-medium"><?php echo htmlspecialchars_safe($case['industry']); ?></span>
                                    <?php endif; ?>
                                </div>
                                <h3 class="text-2xl lg:text-3xl font-bold text-[#23332c] mb-4 leading-tight"><?php echo htmlspecialchars_safe($case['title']); ?></h3>
                                <?php if ($case['client_name']): ?>
                                    <p class="text-[#8a978f] mb-5 text-sm flex items-center">
                                        <i class="fas fa-building text-[#3d7a66] mr-2"></i>
                                        <?php echo htmlspecialchars_safe($case['client_name']); ?><?php if ($case['location']): ?> — <?php echo htmlspecialchars_safe($case['location']); ?><?php endif; ?>
                                    </p>
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
                                            <div class="result-stat bg-[#f5f7f5] border border-[#e6ece8] p-4 rounded-2xl text-center">
                                                <div class="text-3xl font-bold text-[#23332c] mb-1"><?php echo htmlspecialchars_safe($matches[1]); ?></div>
                                                <div class="text-xs text-[#7d8b84]"><?php echo htmlspecialchars_safe($matches[2]); ?></div>
                                            </div>
                                        <?php 
                                            endif;
                                        endforeach; 
                                        ?>
                                    </div>
                                <?php endif; ?>

                                <?php if ($case['challenge']): ?>
                                    <h4 class="text-lg font-semibold text-[#23332c] mb-2 flex items-center">
                                        <i class="fas fa-exclamation-circle text-[#3d7a66] mr-2"></i> Challenge
                                    </h4>
                                    <p class="text-[#5a6b62] mb-6 leading-relaxed text-sm"><?php echo nl2br_safe($case['challenge']); ?></p>
                                <?php endif; ?>

                                <?php if ($case['solution']): ?>
                                    <h4 class="text-lg font-semibold text-[#23332c] mb-2 flex items-center">
                                        <i class="fas fa-lightbulb text-[#3d7a66] mr-2"></i> Solution
                                    </h4>
                                    <p class="text-[#5a6b62] mb-6 leading-relaxed text-sm"><?php echo nl2br_safe($case['solution']); ?></p>
                                <?php endif; ?>

                                <?php if ($case['results']): ?>
                                    <h4 class="text-lg font-semibold text-[#23332c] mb-3 flex items-center">
                                        <i class="fas fa-chart-line text-[#3d7a66] mr-2"></i> Results
                                    </h4>
                                    <ul class="text-[#5a6b62] space-y-2 mb-6">
                                        <?php 
                                        $results_list = explode("\n", $case['results']);
                                        foreach ($results_list as $result_item):
                                            $result_item = trim($result_item);
                                            if ($result_item):
                                        ?>
                                            <li class="flex items-start text-sm"><i class="fas fa-check-circle text-[#3d7a66] mr-3 mt-1"></i><?php echo htmlspecialchars_safe($result_item); ?></li>
                                        <?php 
                                            endif;
                                        endforeach; 
                                        ?>
                                    </ul>
                                <?php endif; ?>

                            </div>
                            <div class="details-panel lg:w-1/3 p-8 lg:p-10 flex flex-col justify-center border-t lg:border-t-0 lg:border-l border-[#e6ece8]">
                                <h4 class="text-lg font-semibold text-[#23332c] mb-5 flex items-center">
                                    <i class="fas fa-clipboard-list text-[#3d7a66] mr-2"></i> Project Details
                                </h4>
                                <div class="space-y-4">
                                    <?php if ($case['industry']): ?>
                                        <div>
                                            <div class="text-xs text-[#8a978f] mb-1 uppercase tracking-wider">Industry</div>
                                            <div class="font-medium text-[#23332c] text-sm"><?php echo htmlspecialchars_safe($case['industry']); ?></div>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($case['client_name']): ?>
                                        <div>
                                            <div class="text-xs text-[#8a978f] mb-1 uppercase tracking-wider">Client</div>
                                            <div class="font-medium text-[#23332c] text-sm"><?php echo htmlspecialchars_safe($case['client_name']); ?></div>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($case['location']): ?>
                                        <div>
                                            <div class="text-xs text-[#8a978f] mb-1 uppercase tracking-wider">Location</div>
                                            <div class="font-medium text-[#23332c] text-sm"><?php echo htmlspecialchars_safe($case['location']); ?></div>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($case['created_at']): ?>
                                        <div>
                                            <div class="text-xs text-[#8a978f] mb-1 uppercase tracking-wider">Date</div>
                                            <div class="font-medium text-[#23332c] text-sm"><?php echo date('Y', strtotime($case['created_at'])); ?></div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <?php if ($case['image_url']): ?>
                                    <div class="mt-6">
                                        <img src="<?php echo htmlspecialchars_safe($case['image_url']); ?>" alt="<?php echo htmlspecialchars_safe($case['title']); ?>" class="w-full rounded-xl">
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="bg-white border border-[#e6ece8] rounded-2xl p-12 text-center reveal">
                    <div class="w-16 h-16 rounded-full bg-[#eef3f0] flex items-center justify-center mx-auto mb-5">
                        <i class="fas fa-folder-open text-2xl text-[#60796e]"></i>
                    </div>
                    <p class="text-[#7d8b84] text-lg">No case studies available at this time. Please check back later.</p>
                </div>
            <?php endif; ?>
        </div>
    </section>

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
                        <i class="fas fa-download text-xs"></i>
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
