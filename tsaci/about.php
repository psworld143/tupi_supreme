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
        
        .timeline::before {
            content: '';
            position: absolute;
            left: 50%;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #2c5530;
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
        
        .timeline-content::before {
            content: '';
            position: absolute;
            top: 20px;
            width: 0;
            height: 0;
            border: 10px solid transparent;
        }
        
        .timeline-item:nth-child(odd) .timeline-content::before {
            right: -20px;
            border-left-color: white;
        }
        
        .timeline-item:nth-child(even) .timeline-content::before {
            left: -20px;
            border-right-color: white;
        }
        
        .timeline-dot {
            position: absolute;
            left: 50%;
            top: 20px;
            width: 20px;
            height: 20px;
            background: #8bc34a;
            border-radius: 50%;
            transform: translateX(-50%);
            z-index: 2;
        }
        
        .team-card {
            transition: transform 0.3s ease;
        }
        
        .team-card:hover {
            transform: translateY(-5px);
        }
        
        .team-photo {
            background: linear-gradient(135deg, #8bc34a, #4a7c59);
        }
        
        .value-icon {
            background: linear-gradient(135deg, #8bc34a, #4a7c59);
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
            
            .timeline-item .timeline-content::before {
                left: -20px !important;
                right: auto !important;
                border-right-color: white !important;
                border-left-color: transparent !important;
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

    <!-- Company Story -->
    <section class="py-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                <div>
                    <h2 class="text-4xl lg:text-5xl font-bold text-gray-900 mb-6"><?php echo htmlspecialchars_safe($company_story_title); ?></h2>
                    <?php if ($company_story_content): ?>
                        <div class="text-gray-600 text-lg prose prose-lg max-w-none">
                            <?php echo $company_story_content; ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="text-center">
                    <i class="<?php echo htmlspecialchars_safe($company_story_icon); ?> text-8xl text-primary opacity-75"></i>
                </div>
            </div>
        </div>
    </section>

    <!-- Mission & Vision -->
    <section class="py-20 bg-light">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <div class="bg-white rounded-2xl shadow-lg p-8 h-full">
                    <div class="text-center mb-6">
                        <i class="<?php echo htmlspecialchars_safe($mission_icon); ?> text-5xl text-primary"></i>
                    </div>
                    <h3 class="text-3xl font-bold text-center mb-6"><?php echo htmlspecialchars_safe($mission_title); ?></h3>
                    <div class="text-gray-600 text-center prose prose-lg max-w-none">
                        <?php echo $mission_content; ?>
                    </div>
                </div>
                <div class="bg-white rounded-2xl shadow-lg p-8 h-full">
                    <div class="text-center mb-6">
                        <i class="<?php echo htmlspecialchars_safe($vision_icon); ?> text-5xl text-primary"></i>
                    </div>
                    <h3 class="text-3xl font-bold text-center mb-6"><?php echo htmlspecialchars_safe($vision_title); ?></h3>
                    <div class="text-gray-600 text-center prose prose-lg max-w-none">
                        <?php echo $vision_content; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Company Timeline -->
    <?php if (!empty($timeline_events)): ?>
    <section class="py-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-4xl lg:text-5xl font-bold text-gray-900 mb-4"><?php echo htmlspecialchars_safe($timeline_title); ?></h2>
                <p class="text-xl text-gray-600"><?php echo htmlspecialchars_safe($timeline_subtitle); ?></p>
            </div>
            <div class="timeline relative">
                <?php foreach ($timeline_events as $index => $event): ?>
                    <div class="timeline-item relative mb-10">
                        <div class="timeline-dot"></div>
                        <div class="timeline-content bg-white p-6 rounded-lg shadow-lg relative">
                            <h4 class="text-xl font-bold mb-2"><?php echo htmlspecialchars_safe($event['year']); ?> - <?php echo htmlspecialchars_safe($event['title']); ?></h4>
                            <p class="text-gray-600"><?php echo htmlspecialchars_safe($event['description']); ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Core Values -->
    <?php if (!empty($company_values)): ?>
    <section class="bg-light py-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-4xl lg:text-5xl font-bold text-gray-900 mb-4"><?php echo htmlspecialchars_safe($values_title); ?></h2>
                <p class="text-xl text-gray-600"><?php echo htmlspecialchars_safe($values_subtitle); ?></p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <?php foreach ($company_values as $value): ?>
                    <div class="bg-white rounded-2xl shadow-lg p-8 text-center">
                        <?php if ($value['icon']): ?>
                            <div class="value-icon w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-6">
                                <i class="<?php echo htmlspecialchars_safe($value['icon']); ?> text-3xl text-white"></i>
                            </div>
                        <?php endif; ?>
                        <h4 class="text-2xl font-bold text-gray-900 mb-4"><?php echo htmlspecialchars_safe($value['title']); ?></h4>
                        <p class="text-gray-600"><?php echo htmlspecialchars_safe($value['description']); ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Leadership Team -->
    <?php if (!empty($team_members)): ?>
    <section class="py-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-4xl lg:text-5xl font-bold text-gray-900 mb-4"><?php echo htmlspecialchars_safe($team_title); ?></h2>
                <p class="text-xl text-gray-600"><?php echo htmlspecialchars_safe($team_subtitle); ?></p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <?php foreach ($team_members as $member): ?>
                    <div class="team-card bg-white rounded-2xl shadow-lg p-8 text-center">
                        <?php if ($member['photo_url']): ?>
                            <img src="<?php echo htmlspecialchars_safe($member['photo_url']); ?>" alt="<?php echo htmlspecialchars_safe($member['name']); ?>" class="w-32 h-32 rounded-full mx-auto mb-6 object-cover">
                        <?php else: ?>
                            <div class="team-photo w-32 h-32 rounded-full flex items-center justify-center mx-auto mb-6">
                                <i class="fas fa-user text-5xl text-white"></i>
                            </div>
                        <?php endif; ?>
                        <h5 class="text-2xl font-bold text-gray-900 mb-2"><?php echo htmlspecialchars_safe($member['name']); ?></h5>
                        <?php if ($member['position']): ?>
                            <p class="text-gray-500 mb-4"><?php echo htmlspecialchars_safe($member['position']); ?></p>
                        <?php endif; ?>
                        <?php if ($member['bio']): ?>
                            <p class="text-gray-600"><?php echo htmlspecialchars_safe($member['bio']); ?></p>
                        <?php endif; ?>
                        <?php if ($member['email']): ?>
                            <a href="mailto:<?php echo htmlspecialchars_safe($member['email']); ?>" class="text-primary hover:text-secondary mt-2 inline-block">
                                <i class="fas fa-envelope mr-1"></i>Email
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <?php include 'includes/footer.php'; ?>
</body>
</html>
