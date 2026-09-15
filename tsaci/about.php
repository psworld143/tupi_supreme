<?php
require_once 'includes/config.php';

$current_page = 'about';

// Get dynamic content - all from database
$page_header = getAboutContent('page_header_title');
$page_header_title = $page_header && !empty($page_header['title']) ? $page_header['title'] : 'About Tupi Supreme';
$page_header_subtitle_content = getAboutContent('page_header_subtitle');
$page_header_subtitle = $page_header_subtitle_content && !empty($page_header_subtitle_content['content']) ? $page_header_subtitle_content['content'] : 'Leading the way in activated carbon solutions for a sustainable future';

// Company Story - from database
$company_story = getAboutContent('company_story_content');
$company_story_title_content = getAboutContent('company_story_title');
$company_story_title = $company_story_title_content && !empty($company_story_title_content['title']) ? $company_story_title_content['title'] : 'Our Story';
// Allow HTML content from CKEditor - content is already sanitized when saved
$company_story_content = $company_story && !empty($company_story['content']) ? $company_story['content'] : '';
$company_story_icon_content = getAboutContent('company_story_icon');
$company_story_icon = $company_story_icon_content && !empty($company_story_icon_content['content']) ? $company_story_icon_content['content'] : 'fas fa-industry';

// Mission - from database
$mission = getAboutContent('mission_content');
$mission_title_content = getAboutContent('mission_title');
$mission_title = $mission_title_content && !empty($mission_title_content['title']) ? $mission_title_content['title'] : 'Our Mission';
// Allow HTML content from CKEditor
$mission_content = $mission && !empty($mission['content']) ? $mission['content'] : '';
$mission_icon_content = getAboutContent('mission_icon');
$mission_icon = $mission_icon_content && !empty($mission_icon_content['content']) ? $mission_icon_content['content'] : 'fas fa-bullseye';

// Vision - from database
$vision = getAboutContent('vision_content');
$vision_title_content = getAboutContent('vision_title');
$vision_title = $vision_title_content && !empty($vision_title_content['title']) ? $vision_title_content['title'] : 'Our Vision';
// Allow HTML content from CKEditor
$vision_content = $vision && !empty($vision['content']) ? $vision['content'] : '';
$vision_icon_content = getAboutContent('vision_icon');
$vision_icon = $vision_icon_content && !empty($vision_icon_content['content']) ? $vision_icon_content['content'] : 'fas fa-eye';

// Timeline (Our Journey) - from database
$timeline_events = getTimelineEvents();
$timeline_title_content = getAboutContent('timeline_title');
$timeline_title = $timeline_title_content && !empty($timeline_title_content['title']) ? $timeline_title_content['title'] : 'Our Journey';
$timeline_subtitle_content = getAboutContent('timeline_subtitle');
$timeline_subtitle = $timeline_subtitle_content && !empty($timeline_subtitle_content['content']) ? $timeline_subtitle_content['content'] : 'Milestones that shaped our company\'s growth and success';

// Company Values (Our Core Values) - from database
$company_values = getCompanyValues();
$values_title_content = getAboutContent('values_title');
$values_title = $values_title_content && !empty($values_title_content['title']) ? $values_title_content['title'] : 'Our Core Values';
$values_subtitle_content = getAboutContent('values_subtitle');
$values_subtitle = $values_subtitle_content && !empty($values_subtitle_content['content']) ? $values_subtitle_content['content'] : 'The principles that guide everything we do';

$team_title_content = getAboutContent('team_title');
$team_title = $team_title_content ? $team_title_content['title'] : 'Our Leadership Team';
$team_subtitle_content = getAboutContent('team_subtitle');
$team_subtitle = $team_subtitle_content ? $team_subtitle_content['content'] : 'Meet the experienced professionals driving our success';
$team_members = [];
$db = getDB();
if ($db) {
    $result = $db->query("SELECT * FROM team_members WHERE is_active = 1 ORDER BY display_order");
    while ($row = $result->fetch_assoc()) {
        $team_members[] = $row;
    }
}

$company_name = getSiteSetting('company_short_name', 'Tupi Supreme');
$company_full_name = getSiteSetting('company_name', 'Tupi Supreme Activated Carbon, Inc.');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - <?php echo htmlspecialchars_safe($company_full_name); ?></title>
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

        /* Story / value icon container */
        .story-icon-wrap {
            background: linear-gradient(135deg, #3d7a66, #60796e);
            transition: transform 0.35s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .story-card:hover .story-icon-wrap {
            transform: scale(1.06) rotate(-3deg);
        }

        /* Mission / Vision cards */
        .mv-card {
            transition: transform 0.35s cubic-bezier(0.4, 0, 0.2, 1), border-color 0.35s ease, box-shadow 0.35s ease;
        }
        .mv-card:hover {
            transform: translateY(-6px);
            border-color: #3d7a66;
            box-shadow: 0 12px 32px -12px rgba(35, 51, 44, 0.15);
        }
        .mv-icon-wrap {
            background: linear-gradient(135deg, #3d7a66, #60796e);
            transition: transform 0.35s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .mv-card:hover .mv-icon-wrap {
            transform: scale(1.06);
        }

        /* Value cards */
        .value-card {
            transition: transform 0.35s cubic-bezier(0.4, 0, 0.2, 1), border-color 0.35s ease, box-shadow 0.35s ease;
        }
        .value-card:hover {
            transform: translateY(-6px);
            border-color: #3d7a66;
            box-shadow: 0 12px 32px -12px rgba(35, 51, 44, 0.15);
        }
        .value-card:hover .value-icon {
            transform: scale(1.08) rotate(-3deg);
        }
        .value-icon {
            background: linear-gradient(135deg, #3d7a66, #60796e);
            transition: transform 0.35s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* Team cards */
        .team-card {
            transition: transform 0.35s cubic-bezier(0.4, 0, 0.2, 1), border-color 0.35s ease, box-shadow 0.35s ease;
        }
        .team-card:hover {
            transform: translateY(-6px);
            border-color: #3d7a66;
            box-shadow: 0 12px 32px -12px rgba(35, 51, 44, 0.15);
        }
        .team-photo {
            background: linear-gradient(135deg, #3d7a66, #60796e);
        }

        /* Timeline — modern vertical rail with alternating cards */
        .timeline::before {
            content: '';
            position: absolute;
            left: 50%;
            top: 0;
            bottom: 0;
            width: 2px;
            background: linear-gradient(180deg, #3d7a66, #c0ccc5);
            transform: translateX(-50%);
        }

        .timeline-item:nth-child(odd) .timeline-content {
            margin-left: 0;
            margin-right: 50%;
            text-align: right;
            padding-right: 30px;
        }

        .timeline-item:nth-child(even) .timeline-content {
            margin-left: 50%;
            margin-right: 0;
            text-align: left;
            padding-left: 30px;
        }

        .timeline-content {
            transition: transform 0.35s cubic-bezier(0.4, 0, 0.2, 1), border-color 0.35s ease, box-shadow 0.35s ease;
        }
        .timeline-content:hover {
            transform: translateY(-4px);
            border-color: #3d7a66;
            box-shadow: 0 12px 32px -12px rgba(35, 51, 44, 0.15);
        }

        .timeline-dot {
            position: absolute;
            left: 50%;
            top: 20px;
            width: 20px;
            height: 20px;
            background: #8bc34a;
            border: 4px solid #f7faf8;
            border-radius: 50%;
            transform: translateX(-50%);
            z-index: 2;
            box-shadow: 0 0 0 2px #3d7a66;
        }

        @media (max-width: 768px) {
            .timeline::before {
                left: 30px;
            }

            .timeline-item .timeline-content {
                margin-left: 60px !important;
                margin-right: 0 !important;
                text-align: left !important;
                padding-left: 20px !important;
                padding-right: 20px !important;
            }

            .timeline-dot {
                left: 30px;
            }
        }

        /* Scroll-triggered reveal — modern alternative to always-on fade-in.
           Elements start hidden and animate in when they enter the viewport. */
        .reveal {
            opacity: 0;
            transform: translateY(24px);
            transition: opacity 0.6s cubic-bezier(0.4, 0, 0.2, 1), transform 0.6s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .reveal.is-visible {
            opacity: 1;
            transform: translateY(0);
        }

        /* Staggered delays for cascading reveal within a section */
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
                    <i class="fas fa-leaf text-xs"></i> Our Company
                </span>
                <h1 class="text-4xl lg:text-6xl font-bold mb-5 leading-tight mt-4 fade-in fade-in-delay-2"><?php echo htmlspecialchars_safe($page_header_title); ?></h1>
                <p class="text-lg lg:text-xl text-white/85 max-w-2xl mx-auto fade-in fade-in-delay-3"><?php echo htmlspecialchars_safe($page_header_subtitle); ?></p>
            </div>
        </div>
    </section>

    <!-- Company Story -->
    <section class="py-20 lg:py-24 relative overflow-hidden">
        <!-- Subtle dot grid backdrop -->
        <div class="absolute inset-0 dot-grid opacity-40"></div>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                <div class="reveal">
                    <span class="eyebrow mb-4">
                        <i class="fas fa-book-open text-xs"></i> Our Story
                    </span>
                    <h2 class="text-3xl lg:text-4xl font-bold text-[#23332c] mb-6 mt-4"><?php echo htmlspecialchars_safe($company_story_title); ?></h2>
                    <?php if ($company_story_content): ?>
                        <div class="text-[#5a6b62] text-base lg:text-lg leading-relaxed prose prose-lg max-w-none">
                            <?php echo $company_story_content; ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="text-center reveal reveal-delay-2">
                    <div class="story-card inline-block">
                        <div class="story-icon-wrap w-40 h-40 rounded-2xl flex items-center justify-center mx-auto">
                            <i class="<?php echo htmlspecialchars_safe($company_story_icon); ?> text-6xl text-white"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Mission & Vision -->
    <section class="py-20 lg:py-24 bg-[#f5f7f5]">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16 reveal">
                <span class="eyebrow mb-4">
                    <i class="fas fa-compass text-xs"></i> Purpose
                </span>
                <h2 class="text-3xl lg:text-4xl font-bold text-[#23332c] mb-4 mt-4">Mission & Vision</h2>
                <p class="text-lg text-[#7d8b84] max-w-2xl mx-auto">The principles that drive us forward</p>
            </div>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="mv-card bg-white border border-[#e6ece8] rounded-2xl p-8 h-full reveal reveal-delay-1">
                    <div class="text-center mb-6">
                        <div class="mv-icon-wrap w-20 h-20 rounded-2xl flex items-center justify-center mx-auto">
                            <i class="<?php echo htmlspecialchars_safe($mission_icon); ?> text-3xl text-white"></i>
                        </div>
                    </div>
                    <h3 class="text-2xl font-semibold text-center text-[#23332c] mb-5"><?php echo htmlspecialchars_safe($mission_title); ?></h3>
                    <div class="text-[#5a6b62] text-center prose prose-lg max-w-none leading-relaxed">
                        <?php echo $mission_content; ?>
                    </div>
                </div>
                <div class="mv-card bg-white border border-[#e6ece8] rounded-2xl p-8 h-full reveal reveal-delay-2">
                    <div class="text-center mb-6">
                        <div class="mv-icon-wrap w-20 h-20 rounded-2xl flex items-center justify-center mx-auto">
                            <i class="<?php echo htmlspecialchars_safe($vision_icon); ?> text-3xl text-white"></i>
                        </div>
                    </div>
                    <h3 class="text-2xl font-semibold text-center text-[#23332c] mb-5"><?php echo htmlspecialchars_safe($vision_title); ?></h3>
                    <div class="text-[#5a6b62] text-center prose prose-lg max-w-none leading-relaxed">
                        <?php echo $vision_content; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Company Timeline -->
    <?php if (!empty($timeline_events)): ?>
    <section class="py-20 lg:py-24 relative overflow-hidden">
        <div class="absolute inset-0 dot-grid opacity-40"></div>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative">
            <div class="text-center mb-16 reveal">
                <span class="eyebrow mb-4">
                    <i class="fas fa-history text-xs"></i> Milestones
                </span>
                <h2 class="text-3xl lg:text-4xl font-bold text-[#23332c] mb-4 mt-4"><?php echo htmlspecialchars_safe($timeline_title); ?></h2>
                <p class="text-lg text-[#7d8b84] max-w-2xl mx-auto"><?php echo htmlspecialchars_safe($timeline_subtitle); ?></p>
            </div>
            <div class="timeline relative">
                <?php foreach ($timeline_events as $index => $event): ?>
                    <div class="timeline-item relative mb-10 reveal <?php echo 'reveal-delay-' . ((($index % 4) + 1)); ?>">
                        <div class="timeline-dot"></div>
                        <div class="timeline-content bg-white border border-[#e6ece8] p-6 rounded-2xl relative">
                            <span class="eyebrow mb-3 <?php echo ($index % 2 === 0) ? '' : ''; ?>"><?php echo htmlspecialchars_safe($event['year']); ?></span>
                            <h4 class="text-xl font-semibold text-[#23332c] mb-2 mt-2"><?php echo htmlspecialchars_safe($event['title']); ?></h4>
                            <p class="text-[#7d8b84] leading-relaxed text-sm"><?php echo htmlspecialchars_safe($event['description']); ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Core Values -->
    <?php if (!empty($company_values)): ?>
    <section class="bg-[#23332c] text-white py-20 lg:py-24 relative overflow-hidden">
        <!-- Decorative orbs -->
        <div class="orb orb-1" style="background: #3d7a66; opacity: 0.25;"></div>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative">
            <div class="text-center mb-16 reveal">
                <span class="eyebrow bg-white/15 text-white/90 mb-4">
                    <i class="fas fa-heart text-xs"></i> Principles
                </span>
                <h2 class="text-3xl lg:text-4xl font-bold mb-4 mt-4"><?php echo htmlspecialchars_safe($values_title); ?></h2>
                <p class="text-lg text-white/70 max-w-2xl mx-auto"><?php echo htmlspecialchars_safe($values_subtitle); ?></p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($company_values as $i => $value): ?>
                    <div class="value-card bg-white/5 backdrop-blur-sm border border-white/10 rounded-2xl p-8 text-center reveal <?php echo 'reveal-delay-' . ((($i % 3) + 1)); ?>">
                        <?php if ($value['icon']): ?>
                            <div class="value-icon w-16 h-16 rounded-2xl flex items-center justify-center mx-auto mb-6">
                                <i class="<?php echo htmlspecialchars_safe($value['icon']); ?> text-2xl text-white"></i>
                            </div>
                        <?php endif; ?>
                        <h4 class="text-xl font-semibold mb-3"><?php echo htmlspecialchars_safe($value['title']); ?></h4>
                        <p class="text-white/70 leading-relaxed text-sm"><?php echo htmlspecialchars_safe($value['description']); ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Leadership Team -->
    <?php if (!empty($team_members)): ?>
    <section class="py-20 lg:py-24">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16 reveal">
                <span class="eyebrow mb-4">
                    <i class="fas fa-users text-xs"></i> Leadership
                </span>
                <h2 class="text-3xl lg:text-4xl font-bold text-[#23332c] mb-4 mt-4"><?php echo htmlspecialchars_safe($team_title); ?></h2>
                <p class="text-lg text-[#7d8b84] max-w-2xl mx-auto"><?php echo htmlspecialchars_safe($team_subtitle); ?></p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($team_members as $i => $member): ?>
                    <div class="team-card bg-white border border-[#e6ece8] rounded-2xl p-8 text-center reveal <?php echo 'reveal-delay-' . ((($i % 3) + 1)); ?>">
                        <?php if ($member['photo_url']): ?>
                            <img src="<?php echo htmlspecialchars_safe($member['photo_url']); ?>" alt="<?php echo htmlspecialchars_safe($member['name']); ?>" class="w-28 h-28 rounded-full mx-auto mb-6 object-cover border-4 border-[#eef3f0]">
                        <?php else: ?>
                            <div class="team-photo w-28 h-28 rounded-full flex items-center justify-center mx-auto mb-6 border-4 border-[#eef3f0]">
                                <i class="fas fa-user text-4xl text-white"></i>
                            </div>
                        <?php endif; ?>
                        <h5 class="text-xl font-semibold text-[#23332c] mb-1"><?php echo htmlspecialchars_safe($member['name']); ?></h5>
                        <?php if ($member['position']): ?>
                            <p class="text-[#3d7a66] font-medium text-sm mb-4"><?php echo htmlspecialchars_safe($member['position']); ?></p>
                        <?php endif; ?>
                        <?php if ($member['bio']): ?>
                            <p class="text-[#7d8b84] leading-relaxed text-sm mb-4"><?php echo htmlspecialchars_safe($member['bio']); ?></p>
                        <?php endif; ?>
                        <?php if ($member['email']): ?>
                            <a href="mailto:<?php echo htmlspecialchars_safe($member['email']); ?>" class="inline-flex items-center gap-2 text-[#3d7a66] hover:text-[#23332c] text-sm font-medium transition-colors">
                                <i class="fas fa-envelope text-xs"></i> Email
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <?php include 'includes/footer.php'; ?>

    <!-- Scroll-triggered reveal animations -->
    <script>
        (function() {
            const reveals = document.querySelectorAll('.reveal');
            if (!reveals.length) return;

            // Fallback: if IntersectionObserver isn't supported, show everything
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
