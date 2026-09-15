<?php
require_once 'includes/config.php';

$current_page = 'contact';

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

        /* Contact cards */
        .contact-card {
            transition: transform 0.35s cubic-bezier(0.4, 0, 0.2, 1), border-color 0.35s ease, box-shadow 0.35s ease;
        }
        .contact-card:hover {
            transform: translateY(-6px);
            border-color: #3d7a66;
            box-shadow: 0 12px 32px -12px rgba(35, 51, 44, 0.15);
        }
        .contact-card:hover .contact-icon {
            transform: scale(1.08) rotate(-3deg);
        }
        .contact-icon {
            background: linear-gradient(135deg, #3d7a66, #60796e);
            transition: transform 0.35s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* Form inputs */
        .form-input {
            transition: border-color 0.3s ease, box-shadow 0.3s ease, background-color 0.3s ease;
        }
        .form-input:focus {
            border-color: #3d7a66;
            box-shadow: 0 0 0 3px rgba(61, 122, 102, 0.15);
            outline: none;
        }
        .form-input:hover:not(:focus) {
            border-color: #c0ccc5;
        }

        /* Map container */
        .map-container {
            border-radius: 1rem;
            overflow: hidden;
            border: 1px solid #e6ece8;
        }

        /* Office hours */
        .hours-item {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #e6ece8;
        }
        .hours-item:last-child {
            border-bottom: none;
        }

        /* FAQ accordion */
        .faq-item {
            transition: border-color 0.3s ease;
        }
        .faq-item:hover {
            border-color: #3d7a66;
        }
        .faq-button:hover {
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
    <section id="page-header" class="page-header-gradient text-white relative overflow-hidden pt-24 pb-20">
        <!-- Floating gradient orbs for depth -->
        <div class="orb orb-1"></div>
        <div class="orb orb-2"></div>
        <div class="page-header-pattern absolute inset-0 opacity-30"></div>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="text-center">
                <span class="eyebrow bg-white/15 text-white/90 mb-5 fade-in fade-in-delay-1">
                    <i class="fas fa-envelope text-xs"></i> Get In Touch
                </span>
                <h1 class="text-4xl lg:text-6xl font-bold mb-5 leading-tight mt-4 fade-in fade-in-delay-2"><?php echo htmlspecialchars_safe($page_header_title); ?></h1>
                <p class="text-lg lg:text-xl text-white/85 max-w-2xl mx-auto fade-in fade-in-delay-3"><?php echo htmlspecialchars_safe($page_header_subtitle); ?></p>
            </div>
        </div>
    </section>

    <!-- Contact Information -->
    <section id="contact-section" class="py-20 lg:py-24 relative overflow-hidden">
        <!-- Subtle dot grid backdrop -->
        <div class="absolute inset-0 dot-grid opacity-40"></div>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative">
            <div class="text-center mb-16 reveal">
                <span class="eyebrow mb-4">
                    <i class="fas fa-address-book text-xs"></i> Contact Info
                </span>
                <h2 class="text-3xl lg:text-4xl font-bold text-[#23332c] mb-4 mt-4"><?php echo htmlspecialchars_safe($contact_section_title); ?></h2>
                <p class="text-lg text-[#7d8b84] max-w-2xl mx-auto"><?php echo htmlspecialchars_safe($contact_section_subtitle); ?></p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <?php $card_index = 0; ?>
                <?php if (!empty($contact_addresses)): ?>
                    <?php foreach ($contact_addresses as $address): $card_index++; ?>
                        <div class="contact-card bg-white border border-[#e6ece8] rounded-2xl p-8 text-center reveal <?php echo 'reveal-delay-' . ((($card_index - 1) % 3) + 1); ?>">
                            <div class="contact-icon w-16 h-16 rounded-2xl flex items-center justify-center mx-auto mb-6">
                                <i class="fas <?php echo htmlspecialchars_safe($address['icon'] ?: 'fa-map-marker-alt'); ?> text-2xl text-white"></i>
                            </div>
                            <h4 class="text-lg font-semibold text-[#23332c] mb-3"><?php echo htmlspecialchars_safe($address['label'] ?: 'Visit Us'); ?></h4>
                            <p class="text-[#7d8b84] leading-relaxed text-sm"><?php echo nl2br_safe($address['value']); ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
                
                <?php if (!empty($contact_phones)): $card_index++; ?>
                    <div class="contact-card bg-white border border-[#e6ece8] rounded-2xl p-8 text-center reveal <?php echo 'reveal-delay-' . ((($card_index - 1) % 3) + 1); ?>">
                        <div class="contact-icon w-16 h-16 rounded-2xl flex items-center justify-center mx-auto mb-6">
                            <i class="fas fa-phone text-2xl text-white"></i>
                        </div>
                        <h4 class="text-lg font-semibold text-[#23332c] mb-3">Call Us</h4>
                        <div class="text-[#7d8b84] text-sm space-y-1">
                            <?php foreach ($contact_phones as $contact_phone): ?>
                                <p><?php echo htmlspecialchars_safe($contact_phone['label'] ?: 'Phone'); ?>: <span class="font-medium text-[#23332c]"><?php echo htmlspecialchars_safe($contact_phone['value']); ?></span></p>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($contact_emails)): $card_index++; ?>
                    <div class="contact-card bg-white border border-[#e6ece8] rounded-2xl p-8 text-center reveal <?php echo 'reveal-delay-' . ((($card_index - 1) % 3) + 1); ?>">
                        <div class="contact-icon w-16 h-16 rounded-2xl flex items-center justify-center mx-auto mb-6">
                            <i class="fas fa-envelope text-2xl text-white"></i>
                        </div>
                        <h4 class="text-lg font-semibold text-[#23332c] mb-3">Email Us</h4>
                        <div class="text-[#7d8b84] text-sm space-y-1">
                            <?php foreach ($contact_emails as $contact_email): ?>
                                <p><?php echo htmlspecialchars_safe($contact_email['label'] ?: 'Email'); ?>: <a href="mailto:<?php echo htmlspecialchars_safe($contact_email['value']); ?>" class="text-[#3d7a66] hover:underline font-medium"><?php echo htmlspecialchars_safe($contact_email['value']); ?></a></p>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Contact Form -->
    <section id="contact-form" class="bg-[#f5f7f5] py-20 lg:py-24">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white border border-[#e6ece8] rounded-2xl p-8 lg:p-10 reveal">
                <div class="text-center mb-8">
                    <span class="eyebrow mb-4">
                        <i class="fas fa-paper-plane text-xs"></i> Send Message
                    </span>
                    <h2 class="text-2xl lg:text-3xl font-bold text-[#23332c] mb-3 mt-4"><?php echo htmlspecialchars_safe($form_title); ?></h2>
                    <p class="text-[#7d8b84]"><?php echo htmlspecialchars_safe($form_subtitle); ?></p>
                </div>
                
                <?php if ($message): ?>
                    <div class="mb-6 p-4 rounded-2xl flex items-start gap-3 <?php echo $messageType === 'success' ? 'bg-[#eef3f0] text-[#23332c] border border-[#c0ccc5]' : 'bg-red-50 text-red-700 border border-red-200'; ?>">
                        <i class="fas <?php echo $messageType === 'success' ? 'fa-check-circle text-[#3d7a66]' : 'fa-exclamation-circle text-red-500'; ?> text-lg mt-0.5"></i>
                        <span class="text-sm"><?php echo htmlspecialchars($message); ?></span>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="contact.php">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-5">
                        <div>
                            <label for="name" class="block text-sm font-medium text-[#23332c] mb-2">Full Name *</label>
                            <input type="text" class="form-input w-full px-4 py-3 border border-[#d6ded9] rounded-xl bg-[#f7faf8]" id="name" name="name" value="<?php echo htmlspecialchars($name ?? ''); ?>" required>
                        </div>
                        <div>
                            <label for="email" class="block text-sm font-medium text-[#23332c] mb-2">Email Address *</label>
                            <input type="email" class="form-input w-full px-4 py-3 border border-[#d6ded9] rounded-xl bg-[#f7faf8]" id="email" name="email" value="<?php echo htmlspecialchars($email ?? ''); ?>" required>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-5">
                        <div>
                            <label for="phone" class="block text-sm font-medium text-[#23332c] mb-2">Phone Number</label>
                            <input type="tel" class="form-input w-full px-4 py-3 border border-[#d6ded9] rounded-xl bg-[#f7faf8]" id="phone" name="phone" value="<?php echo htmlspecialchars($phone ?? ''); ?>">
                        </div>
                        <div>
                            <label for="company" class="block text-sm font-medium text-[#23332c] mb-2">Company/Organization</label>
                            <input type="text" class="form-input w-full px-4 py-3 border border-[#d6ded9] rounded-xl bg-[#f7faf8]" id="company" name="company" value="<?php echo htmlspecialchars($company ?? ''); ?>">
                        </div>
                    </div>
                    <div class="mb-5">
                        <label for="title" class="block text-sm font-medium text-[#23332c] mb-2">Title/Role</label>
                        <input type="text" class="form-input w-full px-4 py-3 border border-[#d6ded9] rounded-xl bg-[#f7faf8]" id="title" name="title" value="<?php echo htmlspecialchars($title ?? ''); ?>" placeholder="e.g., Plant Manager, Engineer, Procurement Officer">
                    </div>
                    <div class="mb-5">
                        <label for="subject" class="block text-sm font-medium text-[#23332c] mb-2">Subject *</label>
                        <select class="form-input w-full px-4 py-3 border border-[#d6ded9] rounded-xl bg-[#f7faf8]" id="subject" name="subject" required>
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
                        <label for="message" class="block text-sm font-medium text-[#23332c] mb-2">Message *</label>
                        <textarea class="form-input w-full px-4 py-3 border border-[#d6ded9] rounded-xl bg-[#f7faf8]" id="message" name="message" rows="5" required><?php echo htmlspecialchars($message_text ?? ''); ?></textarea>
                    </div>
                    <div class="text-center">
                        <button type="submit" class="bg-[#23332c] hover:bg-[#3a4a41] text-white font-medium py-3 px-8 rounded-full transition-colors inline-flex items-center justify-center gap-2">
                            <i class="fas fa-paper-plane text-xs"></i> Send Message
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </section>

    <!-- Office Hours -->
    <section id="office-hours" class="py-20 lg:py-24 relative overflow-hidden">
        <div class="absolute inset-0 dot-grid opacity-40"></div>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative">
            <div class="text-center mb-16 reveal">
                <span class="eyebrow mb-4">
                    <i class="fas fa-clock text-xs"></i> Hours
                </span>
                <h2 class="text-3xl lg:text-4xl font-bold text-[#23332c] mb-4 mt-4"><?php echo htmlspecialchars_safe($office_hours_title); ?></h2>
                <p class="text-lg text-[#7d8b84] max-w-2xl mx-auto"><?php echo htmlspecialchars_safe($office_hours_subtitle); ?></p>
            </div>
            <?php if (!empty($office_hours)): ?>
                <div class="max-w-2xl mx-auto reveal">
                    <div class="bg-white border border-[#e6ece8] rounded-2xl p-8">
                        <div class="text-center mb-6">
                            <div class="w-16 h-16 rounded-2xl flex items-center justify-center mx-auto bg-[#eef3f0]">
                                <i class="fas fa-clock text-2xl text-[#3d7a66]"></i>
                            </div>
                        </div>
                        <?php foreach ($office_hours as $hour): ?>
                            <div class="hours-item">
                                <span class="font-medium text-[#23332c] text-sm"><?php echo htmlspecialchars_safe($hour['day_label']); ?></span>
                                <span class="text-[#7d8b84] text-sm"><?php echo htmlspecialchars_safe($hour['hours']); ?></span>
                            </div>
                        <?php endforeach; ?>
                        <?php if ($timezone_note): ?>
                            <div class="text-center mt-6">
                                <p class="text-[#8a978f] text-xs flex items-center justify-center gap-2">
                                    <i class="fas fa-globe text-[#3d7a66]"></i>
                                    <?php echo htmlspecialchars_safe($timezone_note); ?>
                                </p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="text-center py-12 reveal">
                    <div class="w-16 h-16 rounded-full bg-[#eef3f0] flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-clock text-2xl text-[#60796e]"></i>
                    </div>
                    <p class="text-[#7d8b84]">Office hours information not available.</p>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Map Section -->
    <section id="map" class="bg-[#f5f7f5] py-20 lg:py-24">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16 reveal">
                <span class="eyebrow mb-4">
                    <i class="fas fa-map-marker-alt text-xs"></i> Location
                </span>
                <h2 class="text-3xl lg:text-4xl font-bold text-[#23332c] mb-4 mt-4"><?php echo htmlspecialchars_safe($map_title); ?></h2>
                <p class="text-lg text-[#7d8b84] max-w-2xl mx-auto"><?php echo htmlspecialchars_safe($map_subtitle); ?></p>
            </div>
            <?php 
            // Ensure we have a valid map URL
            $map_url = trim($map_embed_url);
            
            // Only clear if it's the placeholder New York address (not a real location)
            $placeholder_url = 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3024.2219901290355!2d-74.00369368400567!3d40.71312937933185!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x89c25a23e28c1191%3A0x49f75d3281df052a!2s150%20Park%20Row%2C%20New%20York%2C%20NY%2010007%2C%20USA!5e0!3m2!1sen!2sus!4v1640995200000!5m2!1sen!2sus';
            
            // Check if URL is valid Google Maps embed URL and not the placeholder
            $is_valid_map_url = !empty($map_url) && 
                                strpos($map_url, 'https://www.google.com/maps/embed') === 0 &&
                                $map_url !== $placeholder_url;
            
            // FORCE SHOW MAP IF URL EXISTS AND IS NOT EMPTY
            $force_show = !empty($map_url) && strpos($map_url, 'https://www.google.com/maps/embed') === 0;
            ?>
            
            <?php if (isset($_GET['debug'])): ?>
                <div class="max-w-5xl mx-auto mb-4 p-4 bg-yellow-50 border border-yellow-400 rounded-2xl text-xs">
                    <strong>DEBUG INFO:</strong><br>
                    Map URL Length: <?php echo strlen($map_url); ?><br>
                    Is Empty: <?php echo empty($map_url) ? 'YES' : 'NO'; ?><br>
                    Starts with embed: <?php echo (strpos($map_url, 'https://www.google.com/maps/embed') === 0) ? 'YES' : 'NO'; ?><br>
                    Is NOT placeholder: <?php echo ($map_url !== $placeholder_url) ? 'YES' : 'NO'; ?><br>
                    Is Valid: <?php echo $is_valid_map_url ? 'YES' : 'NO'; ?><br>
                    URL Preview: <?php echo htmlspecialchars(substr($map_url, 0, 100)); ?>...<br>
                    DB Connection: <?php $db = getDB(); echo $db ? 'Connected' : 'Failed'; ?><br>
                </div>
            <?php endif; ?>
            
            <?php if ($is_valid_map_url || $force_show): ?>
                <div class="max-w-5xl mx-auto reveal">
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
                <div class="max-w-5xl mx-auto reveal">
                    <div class="map-container bg-[#eef3f0] flex items-center justify-center" style="height: 450px;">
                        <div class="text-center p-8">
                            <div class="w-16 h-16 rounded-full bg-white flex items-center justify-center mx-auto mb-4">
                                <i class="fas fa-map-marker-alt text-3xl text-[#60796e]"></i>
                            </div>
                            <p class="text-base font-semibold text-[#23332c] mb-2">Map location not configured</p>
                            <p class="text-sm text-[#7d8b84]">Please configure the map embed URL in the admin panel.</p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- FAQ Section -->
    <section id="faqs" class="py-20 lg:py-24 relative overflow-hidden">
        <div class="absolute inset-0 dot-grid opacity-40"></div>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative">
            <div class="text-center mb-16 reveal">
                <span class="eyebrow mb-4">
                    <i class="fas fa-question-circle text-xs"></i> FAQ
                </span>
                <h2 class="text-3xl lg:text-4xl font-bold text-[#23332c] mb-4 mt-4"><?php echo htmlspecialchars_safe($faqs_title); ?></h2>
                <p class="text-lg text-[#7d8b84] max-w-2xl mx-auto"><?php echo htmlspecialchars_safe($faqs_subtitle); ?></p>
            </div>
            <?php if (!empty($contact_faqs)): ?>
                <div class="max-w-4xl mx-auto">
                    <div class="space-y-4">
                        <?php foreach ($contact_faqs as $index => $faq): 
                            $faqId = 'contact-faq' . ($index + 1);
                        ?>
                            <div class="faq-item bg-white border border-[#e6ece8] rounded-2xl overflow-hidden reveal <?php echo 'reveal-delay-' . ((($index % 3) + 1)); ?>">
                                <button class="faq-button w-full px-6 py-5 text-left transition-colors focus:outline-none" onclick="toggleFAQ('<?php echo $faqId; ?>')">
                                    <div class="flex justify-between items-center">
                                        <span class="text-base font-semibold text-[#23332c] pr-4"><?php echo htmlspecialchars_safe($faq['question']); ?></span>
                                        <i class="fas fa-chevron-down text-[#3d7a66] transform transition-transform flex-shrink-0" id="<?php echo $faqId; ?>-icon"></i>
                                    </div>
                                </button>
                                <div class="px-6 pb-5 hidden" id="<?php echo $faqId; ?>-content">
                                    <p class="text-[#7d8b84] leading-relaxed text-sm"><?php echo nl2br_safe($faq['answer']); ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="text-center py-12 reveal">
                    <div class="w-16 h-16 rounded-full bg-[#eef3f0] flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-question-circle text-2xl text-[#60796e]"></i>
                    </div>
                    <p class="text-[#7d8b84]">No FAQs available at this time.</p>
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
