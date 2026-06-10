<?php
require_once 'includes/config.php';

// Contact form processing
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Basic form validation
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $company = trim($_POST['company'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message_text = trim($_POST['message'] ?? '');
    
    $errors = [];
    
    // Validation
    if (empty($name)) {
        $errors[] = 'Name is required';
    }
    
    if (empty($email)) {
        $errors[] = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address';
    }
    
    if (empty($subject)) {
        $errors[] = 'Subject is required';
    }
    
    if (empty($message_text)) {
        $errors[] = 'Message is required';
    }
    
    if (empty($errors)) {
        // Save to database
        if (saveContactMessage($name, $email, $phone, $company, $subject, $message_text)) {
            // Also send email notification
            $to = getSiteSetting('contact_email', 'info@tupisupreme.com');
            $email_subject = 'Contact Form Submission: ' . $subject;
            
            $email_body = "You have received a new contact form submission:\n\n";
            $email_body .= "Name: " . $name . "\n";
            $email_body .= "Email: " . $email . "\n";
            $email_body .= "Phone: " . $phone . "\n";
            $email_body .= "Company: " . $company . "\n";
            $email_body .= "Subject: " . $subject . "\n\n";
            $email_body .= "Message:\n" . $message_text . "\n";
            
            $headers = "From: " . $email . "\r\n";
            $headers .= "Reply-To: " . $email . "\r\n";
            $headers .= "X-Mailer: PHP/" . phpversion();
            
            @mail($to, $email_subject, $email_body, $headers);
            
            $message = 'Thank you for your message! We will get back to you soon.';
            $messageType = 'success';
            
            // Clear form data
            $name = $email = $phone = $company = $subject = $message_text = '';
        } else {
            $message = 'Sorry, there was an error sending your message. Please try again.';
            $messageType = 'danger';
        }
    } else {
        $message = 'Please correct the following errors: ' . implode(', ', $errors);
        $messageType = 'danger';
    }
}

// Get dynamic content
$page_header_title = getPageContent('contact', 'page_header_title', 'Contact Us');
$page_header_subtitle = getPageContent('contact', 'page_header_subtitle', 'Get in touch with our team for all your activated carbon needs');
$contact_section_title = getPageContent('contact', 'contact_section_title', 'Get In Touch');
$contact_section_subtitle = getPageContent('contact', 'contact_section_subtitle', 'We\'re here to help with all your activated carbon requirements');
$form_title = getPageContent('contact', 'form_title', 'Send Us a Message');
$form_subtitle = getPageContent('contact', 'form_subtitle', 'Fill out the form below and we\'ll get back to you as soon as possible');
$office_hours_title = getPageContent('contact', 'office_hours_title', 'Office Hours');
$office_hours_subtitle = getPageContent('contact', 'office_hours_subtitle', 'When you can reach our team');
$map_title = getPageContent('contact', 'map_title', 'Find Us');
$map_subtitle = getPageContent('contact', 'map_subtitle', 'Visit our facility or get directions');
$faqs_title = getPageContent('contact', 'faqs_title', 'Frequently Asked Questions');
$faqs_subtitle = getPageContent('contact', 'faqs_subtitle', 'Common questions about our products and services');
$timezone_note = getPageContent('contact', 'timezone_note', 'All times are in Eastern Standard Time (EST)');
$map_embed_url = getPageContent('contact', 'map_embed_url', 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3965.7703916584064!2d124.9847734749907!3d6.293877493695194!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x32f78ee4e9ab4bd5%3A0xa78774472d2fc69d!2sTupi%20Supreme%20Activated%20Carbon%2C%20Inc.!5e0!3m2!1sen!2sph!4v1764687549707!5m2!1sen!2sph');
// Ensure URL is trimmed and not empty
$map_embed_url = trim($map_embed_url);

// Get contact info from database
$contact_addresses = getContactInfo('address');
$contact_phones = getContactInfo('phone');
$contact_emails = getContactInfo('email');
$office_hours = getOfficeHours();
$contact_faqs = getFAQs(5); // Get first 5 FAQs for contact page
$subject_options = getContactSubjectOptions(); // Get contact form subject options
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo htmlspecialchars_safe(getPageContent('contact', 'meta_description', 'Contact Tupi Supreme Activated Carbon, Inc. for all your activated carbon needs. Get in touch with our team for technical consultation, product information, and custom solutions.')); ?>">
    <title>Contact Us - <?php echo htmlspecialchars_safe(getSiteSetting('company_name', 'Tupi Supreme Activated Carbon, Inc.')); ?></title>
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
        
        .contact-card {
            transition: transform 0.3s ease;
        }
        
        .contact-card:hover {
            transform: translateY(-5px);
        }
        
        .contact-icon {
            background: linear-gradient(135deg, #8bc34a, #4a7c59);
        }
        
        .form-input:focus {
            border-color: #2c5530;
            box-shadow: 0 0 0 0.2rem rgba(44, 85, 48, 0.25);
        }
        
        .map-container {
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .hours-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        
        .hours-item:last-child {
            border-bottom: none;
        }
    </style>
</head>
<body class="font-sans">
    <?php 
    $current_page = 'contact';
    include 'includes/navbar.php'; 
    ?>
    
    <!-- Old Navigation Removed - Using Shared Navbar -->
    <!--
    <nav class="bg-dark text-white fixed w-full top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <a href="index.php" class="flex items-center text-xl font-bold">
                    <i class="fas fa-leaf mr-2"></i>Tupi Supreme
                </a>
                <div class="hidden md:block">
                    <div class="ml-10 flex items-baseline space-x-4">
                        <a href="index.php" class="text-gray-300 hover:text-white px-3 py-2 rounded-md text-sm font-medium">Home</a>
                        <a href="about.php" class="text-gray-300 hover:text-white px-3 py-2 rounded-md text-sm font-medium">About Us</a>
                        <a href="products.php" class="text-gray-300 hover:text-white px-3 py-2 rounded-md text-sm font-medium">Products</a>
                        <a href="services.php" class="text-gray-300 hover:text-white px-3 py-2 rounded-md text-sm font-medium">Services</a>
                        <a href="case-studies.php" class="text-gray-300 hover:text-white px-3 py-2 rounded-md text-sm font-medium">Case Studies</a>
                        <a href="gallery.php" class="text-gray-300 hover:text-white px-3 py-2 rounded-md text-sm font-medium">Gallery</a>
                        <a href="resources.php" class="text-gray-300 hover:text-white px-3 py-2 rounded-md text-sm font-medium">Resources</a>
                        <a href="contact.php" class="text-white px-3 py-2 rounded-md text-sm font-medium bg-primary">Contact</a>
                    </div>
                </div>
                <div class="md:hidden">
                    <button id="mobile-menu-button" class="text-white hover:text-gray-300 focus:outline-none focus:text-white" onclick="toggleMobileMenu()">
                        <i class="fas fa-bars text-2xl"></i>
                    </button>
                </div>
            </div>
            <!-- Mobile Menu -->
            <div id="mobile-menu" class="hidden md:hidden bg-dark border-t border-gray-700">
                <div class="px-2 pt-2 pb-3 space-y-1">
                    <a href="index.php" class="block px-3 py-2 rounded-md text-base font-medium text-gray-300 hover:text-white hover:bg-gray-700">Home</a>
                    <a href="about.php" class="block px-3 py-2 rounded-md text-base font-medium text-gray-300 hover:text-white hover:bg-gray-700">About Us</a>
                    <a href="products.php" class="block px-3 py-2 rounded-md text-base font-medium text-gray-300 hover:text-white hover:bg-gray-700">Products</a>
                    <a href="services.php" class="block px-3 py-2 rounded-md text-base font-medium text-gray-300 hover:text-white hover:bg-gray-700">Services</a>
                    <a href="case-studies.php" class="block px-3 py-2 rounded-md text-base font-medium text-gray-300 hover:text-white hover:bg-gray-700">Case Studies</a>
                    <a href="gallery.php" class="block px-3 py-2 rounded-md text-base font-medium text-gray-300 hover:text-white hover:bg-gray-700">Gallery</a>
                    <a href="resources.php" class="block px-3 py-2 rounded-md text-base font-medium text-gray-300 hover:text-white hover:bg-gray-700">Resources</a>
                    <a href="certifications.php" class="block px-3 py-2 rounded-md text-base font-medium text-gray-300 hover:text-white hover:bg-gray-700">Certifications</a>
                    <a href="contact.php" class="block px-3 py-2 rounded-md text-base font-medium text-white bg-primary">Contact</a>
                </div>
            </div>
        </div>
    </nav>

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

    <!-- Contact Information -->
    <section class="py-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-4xl lg:text-5xl font-bold text-gray-900 mb-4"><?php echo htmlspecialchars_safe($contact_section_title); ?></h2>
                <p class="text-xl text-gray-600"><?php echo htmlspecialchars_safe($contact_section_subtitle); ?></p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <?php if (!empty($contact_addresses)): ?>
                    <?php foreach ($contact_addresses as $address): ?>
                        <div class="contact-card bg-white rounded-2xl shadow-lg p-8 text-center">
                            <div class="contact-icon w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-6">
                                <i class="fas <?php echo htmlspecialchars_safe($address['icon'] ?: 'fa-map-marker-alt'); ?> text-3xl text-white"></i>
                            </div>
                            <h4 class="text-2xl font-bold text-gray-900 mb-4"><?php echo htmlspecialchars_safe($address['label'] ?: 'Visit Us'); ?></h4>
                            <p class="text-gray-600"><?php echo nl2br_safe($address['value']); ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
                
                <?php if (!empty($contact_phones)): ?>
                    <div class="contact-card bg-white rounded-2xl shadow-lg p-8 text-center">
                        <div class="contact-icon w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-6">
                            <i class="fas fa-phone text-3xl text-white"></i>
                        </div>
                        <h4 class="text-2xl font-bold text-gray-900 mb-4">Call Us</h4>
                        <div class="text-gray-600">
                            <?php foreach ($contact_phones as $phone): ?>
                                <?php echo htmlspecialchars_safe($phone['label'] ?: 'Phone'); ?>: <?php echo htmlspecialchars_safe($phone['value']); ?><br>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($contact_emails)): ?>
                    <div class="contact-card bg-white rounded-2xl shadow-lg p-8 text-center">
                        <div class="contact-icon w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-6">
                            <i class="fas fa-envelope text-3xl text-white"></i>
                        </div>
                        <h4 class="text-2xl font-bold text-gray-900 mb-4">Email Us</h4>
                        <div class="text-gray-600">
                            <?php foreach ($contact_emails as $email): ?>
                                <?php echo htmlspecialchars_safe($email['label'] ?: 'Email'); ?>: <a href="mailto:<?php echo htmlspecialchars_safe($email['value']); ?>" class="text-primary hover:underline"><?php echo htmlspecialchars_safe($email['value']); ?></a><br>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Contact Form -->
    <section class="py-20 bg-light">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white rounded-2xl shadow-lg p-8">
                <div class="text-center mb-8">
                    <h2 class="text-4xl lg:text-5xl font-bold text-gray-900 mb-4"><?php echo htmlspecialchars_safe($form_title); ?></h2>
                    <p class="text-xl text-gray-600"><?php echo htmlspecialchars_safe($form_subtitle); ?></p>
                </div>
                
                <?php if ($message): ?>
                    <div class="mb-6 p-4 rounded-lg <?php echo $messageType === 'success' ? 'bg-green-100 text-green-700 border border-green-200' : 'bg-red-100 text-red-700 border border-red-200'; ?>">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="contact.php">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Full Name *</label>
                            <input type="text" class="form-input w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent" id="name" name="name" value="<?php echo htmlspecialchars($name ?? ''); ?>" required>
                        </div>
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email Address *</label>
                            <input type="email" class="form-input w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent" id="email" name="email" value="<?php echo htmlspecialchars($email ?? ''); ?>" required>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">Phone Number *</label>
                            <input type="tel" class="form-input w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent" id="phone" name="phone" value="<?php echo htmlspecialchars($phone ?? ''); ?>" required>
                        </div>
                        <div>
                            <label for="company" class="block text-sm font-medium text-gray-700 mb-2">Company/Organization *</label>
                            <input type="text" class="form-input w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent" id="company" name="company" value="<?php echo htmlspecialchars($company ?? ''); ?>" required>
                        </div>
                    </div>
                    <div class="mb-6">
                        <label for="title" class="block text-sm font-medium text-gray-700 mb-2">Title/Role</label>
                        <input type="text" class="form-input w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent" id="title" name="title" value="<?php echo htmlspecialchars($title ?? ''); ?>" placeholder="e.g., Plant Manager, Engineer, Procurement Officer">
                    </div>
                    <div class="mb-6">
                        <label for="subject" class="block text-sm font-medium text-gray-700 mb-2">Subject *</label>
                        <select class="form-input w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent" id="subject" name="subject" required>
                            <option value="">Select an inquiry type</option>
                            <?php if (!empty($subject_options)): ?>
                                <?php foreach ($subject_options as $option): ?>
                                    <option value="<?php echo htmlspecialchars_safe($option['option_text']); ?>" <?php echo (isset($subject) && $subject === $option['option_text']) ? 'selected' : ''; ?>><?php echo htmlspecialchars_safe($option['option_text']); ?></option>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <option value="General Inquiry">General Inquiry</option>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="mb-8">
                        <label for="message" class="block text-sm font-medium text-gray-700 mb-2">Message *</label>
                        <textarea class="form-input w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent" id="message" name="message" rows="5" required><?php echo htmlspecialchars($message_text ?? ''); ?></textarea>
                    </div>
                    <div class="text-center">
                        <button type="submit" class="bg-primary hover:bg-secondary text-white font-bold py-3 px-8 rounded-lg transition duration-300">Send Message</button>
                    </div>
                </form>
            </div>
        </div>
    </section>

    <!-- Office Hours -->
    <section class="bg-light py-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-4xl lg:text-5xl font-bold text-gray-900 mb-4"><?php echo htmlspecialchars_safe($office_hours_title); ?></h2>
                <p class="text-xl text-gray-600"><?php echo htmlspecialchars_safe($office_hours_subtitle); ?></p>
            </div>
            <?php if (!empty($office_hours)): ?>
                <div class="max-w-2xl mx-auto">
                    <div class="bg-white rounded-2xl shadow-lg p-8">
                        <div class="text-center mb-6">
                            <i class="fas fa-clock text-5xl text-primary"></i>
                        </div>
                        <?php foreach ($office_hours as $hour): ?>
                            <div class="hours-item">
                                <span class="font-semibold"><?php echo htmlspecialchars_safe($hour['day_label']); ?></span>
                                <span><?php echo htmlspecialchars_safe($hour['hours']); ?></span>
                            </div>
                        <?php endforeach; ?>
                        <?php if ($timezone_note): ?>
                            <div class="text-center mt-6">
                                <p class="text-gray-500"><?php echo htmlspecialchars_safe($timezone_note); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="text-center py-12">
                    <p class="text-gray-600">Office hours information not available.</p>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Map Section -->
    <section class="py-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-4xl lg:text-5xl font-bold text-gray-900 mb-4"><?php echo htmlspecialchars_safe($map_title); ?></h2>
                <p class="text-xl text-gray-600"><?php echo htmlspecialchars_safe($map_subtitle); ?></p>
            </div>
            <?php 
            // Ensure we have a valid map URL
            $map_url = trim($map_embed_url);
            
            // Only clear if it's the placeholder New York address (not a real location)
            $placeholder_url = 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3024.2219901290355!2d-74.00369368400567!3d40.71312937933185!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x89c25a23e28c1191%3A0x49f75d3281df052a!2s150%20Park%20Row%2C%20New%20York%2C%20NY%2010007%2C%20USA!5e0!3m2!1sen!2sus!4v1640995200000!5m2!1sen!2sus';
            
            // Check if URL is valid Google Maps embed URL and not the placeholder
            // More lenient check - just verify it's a Google Maps embed URL
            $is_valid_map_url = !empty($map_url) && 
                                strpos($map_url, 'https://www.google.com/maps/embed') === 0 &&
                                $map_url !== $placeholder_url;
            ?>
            
            <!-- TEMPORARY DEBUG OUTPUT - REMOVE AFTER FIXING -->
            <?php if (isset($_GET['debug'])): ?>
                <div class="max-w-5xl mx-auto mb-4 p-4 bg-yellow-100 border border-yellow-400 rounded text-xs">
                    <strong>DEBUG INFO:</strong><br>
                    Map URL Length: <?php echo strlen($map_url); ?><br>
                    Is Empty: <?php echo empty($map_url) ? 'YES' : 'NO'; ?><br>
                    Starts with embed: <?php echo (strpos($map_url, 'https://www.google.com/maps/embed') === 0) ? 'YES' : 'NO'; ?><br>
                    Is NOT placeholder: <?php echo ($map_url !== $placeholder_url) ? 'YES' : 'NO'; ?><br>
                    Is Valid: <?php echo $is_valid_map_url ? 'YES ✅' : 'NO ❌'; ?><br>
                    URL Preview: <?php echo htmlspecialchars(substr($map_url, 0, 100)); ?>...<br>
                    DB Connection: <?php $db = getDB(); echo $db ? 'Connected ✅' : 'Failed ❌'; ?><br>
                </div>
            <?php endif; ?>
            
            <?php 
            // FORCE SHOW MAP IF URL EXISTS AND IS NOT EMPTY (temporary fix)
            $force_show = !empty($map_url) && strpos($map_url, 'https://www.google.com/maps/embed') === 0;
            ?>
            
            <?php if ($is_valid_map_url || $force_show): ?>
                <div class="max-w-5xl mx-auto">
                    <div class="map-container">
                        <iframe 
                            src="<?php echo htmlspecialchars($map_url, ENT_QUOTES, 'UTF-8'); ?>" 
                            width="100%" 
                            height="450" 
                            style="border:0;" 
                            allowfullscreen="" 
                            loading="lazy"
                            referrerpolicy="no-referrer-when-downgrade"
                            title="Tupi Supreme Activated Carbon, Inc. Location">
                        </iframe>
                    </div>
                </div>
            <?php else: ?>
                <div class="max-w-5xl mx-auto">
                    <div class="map-container bg-gray-200 flex items-center justify-center" style="height: 450px;">
                        <div class="text-center text-gray-500 p-8">
                            <i class="fas fa-map-marker-alt text-5xl mb-4"></i>
                            <p class="text-lg font-semibold mb-2">Map location not configured</p>
                            <p class="text-sm">Please configure the map embed URL in the admin panel.</p>
                            <?php if (isset($_GET['debug'])): ?>
                                <div class="mt-4 text-left bg-white p-4 rounded text-xs">
                                    <p><strong>Debug Info:</strong></p>
                                    <p>Raw map_embed_url: <?php echo htmlspecialchars(substr($map_embed_url ?? 'NULL', 0, 100)); ?>...</p>
                                    <p>Trimmed map_url: <?php echo htmlspecialchars(substr($map_url ?? 'NULL', 0, 100)); ?>...</p>
                                    <p>URL Length: <?php echo strlen($map_url ?? ''); ?></p>
                                    <p>Is Empty: <?php echo empty($map_url) ? 'YES' : 'NO'; ?></p>
                                    <p>Starts with embed: <?php echo (strpos($map_url ?? '', 'https://www.google.com/maps/embed') === 0) ? 'YES' : 'NO'; ?></p>
                                    <p>Is Placeholder: <?php echo ($map_url === $placeholder_url) ? 'YES' : 'NO'; ?></p>
                                    <p>Is Valid Map URL: <?php echo $is_valid_map_url ? 'YES ✅' : 'NO ❌'; ?></p>
                                    <p>DB Connection: <?php 
                                        $db = getDB();
                                        echo $db ? 'Connected ✅' : 'Failed ❌';
                                    ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- FAQ Section -->
    <section class="py-20 bg-light">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-4xl lg:text-5xl font-bold text-gray-900 mb-4"><?php echo htmlspecialchars_safe($faqs_title); ?></h2>
                <p class="text-xl text-gray-600"><?php echo htmlspecialchars_safe($faqs_subtitle); ?></p>
            </div>
            <?php if (!empty($contact_faqs)): ?>
                <div class="max-w-4xl mx-auto">
                    <div class="space-y-4">
                        <?php foreach ($contact_faqs as $index => $faq): 
                            $faqId = 'contact-faq' . ($index + 1);
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
                </div>
            <?php else: ?>
                <div class="text-center py-12">
                    <p class="text-gray-600">No FAQs available at this time.</p>
                </div>
            <?php endif; ?>
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