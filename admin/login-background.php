<?php
require_once 'config.php';
requireLogin();

$db = getDB();
$error = '';
$success = '';

// Status flash from PRG redirect (avoids form resubmission on refresh)
if (($_GET['status'] ?? '') === 'saved') {
    $success = 'Login page settings saved successfully!';
}

// Editable login-page settings — defaults mirror login.php
$defaults = [
    'login_bg_enabled'         => '0',
    'login_bg_image'           => '',
    'login_bg_overlay'         => '80',
    'login_bg_color'           => '#152e1e',
    'login_bg_gradient_from'   => '#2c5530',
    'login_bg_gradient_to'     => '#152e1e',
    'login_logo'               => '../uploads/images/tupi_supreme_logo.png',
    'login_logo_mode'          => 'both',
    'login_logo_alt'           => '../uploads/images/tupi_supreme_logo.png',
    'login_accent_color'       => '#8bc34a',
    'login_form_bg_color'      => '#f3f8f4',
    'login_button_color'       => '#2c5530',
    'login_brand_line'         => 'Official website of Tupi Supreme Activated Carbon, Inc.',
    'login_brand_eyebrow'      => 'TSACI Admin Portal',
    'login_brand_title'        => 'One sign-in for',
    'login_brand_title_accent' => 'your whole website.',
    'login_brand_description'  => 'Pages, products, services, gallery, messages, and settings — manage the entire TSACI website from a single admin console.',
    'login_pill_1_icon'        => 'fa-file-alt',
    'login_pill_1_text'        => 'Content & Pages',
    'login_pill_2_icon'        => 'fa-cube',
    'login_pill_2_text'        => 'Products & Services',
    'login_pill_3_icon'        => 'fa-envelope',
    'login_pill_3_text'        => 'Messages',
    'login_brand_quote'        => 'Premium activated carbon solutions for cleaner water and a greener tomorrow.',
    'login_brand_footer'       => 'Tupi Supreme Activated Carbon, Inc. · Admin Console',
    'login_form_eyebrow'       => 'Admin Access',
    'login_form_heading'       => 'Welcome back,',
    'login_form_subtext'       => 'Sign in to your admin dashboard',
    'login_button_text'        => 'Sign in to Dashboard',
    'login_help_text'          => 'Trouble signing in?',
    'login_help_link_text'     => 'Contact support',
    'login_help_link_url'      => '../contact.php',
];

$color_keys = ['login_bg_color', 'login_bg_gradient_from', 'login_bg_gradient_to', 'login_accent_color', 'login_form_bg_color', 'login_button_color'];
$icon_keys  = ['login_pill_1_icon', 'login_pill_2_icon', 'login_pill_3_icon'];
$url_keys   = ['login_logo', 'login_logo_alt', 'login_help_link_url', 'login_bg_image'];

// Load current settings
$settings = $defaults;
$res = $db->query("SELECT setting_key, setting_value FROM site_settings WHERE setting_key LIKE 'login\_%'");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        if (array_key_exists($row['setting_key'], $defaults)) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
    }
}

// Handle save (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken()) {
        $error = 'Security token expired or invalid. Please reload the page and try again.';
    } else {
        $pairs = [];
        foreach ($defaults as $key => $def) {
            $raw = $_POST[$key] ?? $def;
            $val = is_string($raw) ? trim($raw) : $def;
            if (in_array($key, $color_keys, true)) {
                if (!preg_match('/^#[0-9a-fA-F]{6}$/', $val)) {
                    $val = $def;
                }
            } elseif (in_array($key, $icon_keys, true)) {
                if (!preg_match('/^fa[a-z0-9-]*$/', $val)) {
                    $val = $def;
                }
            } elseif (in_array($key, $url_keys, true)) {
                $val = str_replace(["'", "\\", "\n", "\r", "<", ">"], '', $val);
                if ($key === 'login_help_link_url' && $val !== ''
                    && (stripos($val, 'javascript:') === 0 || stripos($val, 'data:') === 0)) {
                    $val = $def;
                }
            }
            $pairs[$key] = $val;
        }
        $pairs['login_bg_enabled'] = isset($_POST['login_bg_enabled']) ? '1' : '0';
        $pairs['login_bg_overlay'] = (string) max(0, min(100, intval($_POST['login_bg_overlay'] ?? 80)));
        $pairs['login_logo_mode']  = ($_POST['login_logo_mode'] ?? 'both') === 'separate' ? 'separate' : 'both';

        $stmt = $db->prepare("INSERT INTO site_settings (setting_key, setting_value, updated_by) VALUES (?, ?, ?)
                              ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_by = VALUES(updated_by)");
        $ok = true;
        foreach ($pairs as $key => $value) {
            $stmt->bind_param("ssi", $key, $value, $_SESSION['admin_id']);
            if (!$stmt->execute()) {
                $ok = false;
                $error = 'Error saving settings: ' . $stmt->error;
                break;
            }
        }

        if ($ok) {
            logActivity('update', 'site_settings', 0, 'Updated login page settings');
            redirect('login-background.php?status=saved');
        }
    }

    // Repopulate form after failed save
    if (isset($pairs)) {
        $settings = array_merge($settings, $pairs);
    }
}

// Escaped-value helper for form fields
$s = function ($key) use ($settings) {
    return htmlspecialchars($settings[$key] ?? '', ENT_QUOTES);
};

$bg_enabled = $settings['login_bg_enabled'] === '1';
$view_url = 'login.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/png" href="../uploads/images/favicon.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Page - <?php echo SITE_NAME; ?></title>
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
</head>
<body class="bg-gray-100">
    <!-- Sidebar -->
    <?php include 'includes/sidebar.php'; ?>

    <style>
        @media (min-width: 1024px) {
            .lg\:ml-64 { scrollbar-width: thin; scrollbar-color: #d2dcd5 transparent; }
            .lg\:ml-64::-webkit-scrollbar { width: 8px; }
            .lg\:ml-64::-webkit-scrollbar-track { background: transparent; }
            .lg\:ml-64::-webkit-scrollbar-thumb { background-color: #d2dcd5; border-radius: 4px; border: 2px solid transparent; background-clip: padding-box; }
            .lg\:ml-64::-webkit-scrollbar-thumb:hover { background-color: #c0ccc5; }
        }
        .bg-preview { background-size: cover; background-position: center; }
        .field { width: 100%; padding: 0.5rem 0.75rem; border: 1px solid #d1d5db; border-radius: 0.375rem; font-size: 0.875rem; }
        .field:focus { outline: none; border-color: #2c5530; box-shadow: 0 0 0 1px #2c5530; }
        .field-label { display: block; font-size: 0.75rem; font-weight: 500; color: #4b5563; margin-bottom: 0.25rem; }
    </style>

    <!-- Main Content -->
    <div class="relative lg:ml-64 p-4 lg:p-8">
        <?php $logo_pulse_logo = '../uploads/images/tupi_supreme_logo.png'; $logo_pulse_mode = 'absolute'; include '../includes/logo_pulse_loader.php'; ?>

        <!-- Page Header -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3">
                <div>
                    <h1 class="text-3xl font-bold text-gray-800 flex items-center gap-3">
                        <i class="fas fa-sign-in-alt text-primary"></i> Login Page
                    </h1>
                    <p class="text-sm text-gray-500 mt-1">Customize the admin login page — background, logo, colors, and all text content.</p>
                </div>
                <a href="<?php echo $view_url; ?>" target="_blank" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200 transition-colors inline-flex items-center justify-center">
                    <i class="fas fa-external-link-alt mr-2"></i>View Login Page
                </a>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded mb-4 flex items-start gap-2">
                <i class="fas fa-exclamation-circle mt-0.5"></i>
                <span><?php echo htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded mb-4 flex items-start gap-2">
                <i class="fas fa-check-circle mt-0.5"></i>
                <span><?php echo htmlspecialchars($success); ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" action="login-background.php" onsubmit="return validateImageUpload()">
            <?php echo csrfTokenField(); ?>

            <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
                <div class="xl:col-span-2 space-y-6">

                    <!-- Section: Brand panel background -->
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <h2 class="text-xl font-bold mb-1 flex items-center gap-2"><i class="fas fa-image text-primary"></i> Brand Panel Background</h2>
                        <p class="text-sm text-gray-500 mb-4">The dark left panel. Use a color gradient, or enable a background image with a dark overlay.</p>

                        <div class="space-y-5">
                            <!-- Enable toggle -->
                            <div class="space-y-3 p-4 rounded-lg border border-gray-200 bg-gray-50">
                                <label class="flex items-center cursor-pointer select-none">
                                    <input type="checkbox" name="login_bg_enabled" id="login_bg_enabled" value="1"
                                           <?php echo $bg_enabled ? 'checked' : ''; ?>
                                           class="sr-only peer" onchange="updatePreview()">
                                    <span class="relative w-11 h-6 rounded-full bg-gray-300 transition-colors duration-300 ease-in-out
                                                 peer-checked:bg-primary peer-focus-visible:ring-2 peer-focus-visible:ring-primary/40 peer-focus-visible:ring-offset-2
                                                 after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:w-5 after:h-5
                                                 after:bg-white after:rounded-full after:shadow
                                                 after:transition-transform after:duration-300 after:ease-in-out
                                                 peer-checked:after:translate-x-5
                                                 hover:after:scale-110 active:after:scale-95"></span>
                                    <span class="ml-3 text-sm font-medium text-gray-700">Use background image <span class="text-gray-400 font-normal">(off = gradient only)</span></span>
                                </label>
                            </div>

                            <!-- Colors -->
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <div>
                                    <label class="field-label" for="login_bg_color">Base color</label>
                                    <div class="flex items-center gap-2">
                                        <input type="color" name="login_bg_color" id="login_bg_color" value="<?php echo $s('login_bg_color'); ?>" class="h-9 w-14 rounded border border-gray-300 cursor-pointer bg-white" oninput="updatePreview()">
                                        <span class="text-xs text-gray-400">Under image</span>
                                    </div>
                                </div>
                                <div>
                                    <label class="field-label" for="login_bg_gradient_from">Gradient start</label>
                                    <div class="flex items-center gap-2">
                                        <input type="color" name="login_bg_gradient_from" id="login_bg_gradient_from" value="<?php echo $s('login_bg_gradient_from'); ?>" class="h-9 w-14 rounded border border-gray-300 cursor-pointer bg-white" oninput="updatePreview()">
                                        <span class="text-xs text-gray-400">No image</span>
                                    </div>
                                </div>
                                <div>
                                    <label class="field-label" for="login_bg_gradient_to">Gradient end</label>
                                    <div class="flex items-center gap-2">
                                        <input type="color" name="login_bg_gradient_to" id="login_bg_gradient_to" value="<?php echo $s('login_bg_gradient_to'); ?>" class="h-9 w-14 rounded border border-gray-300 cursor-pointer bg-white" oninput="updatePreview()">
                                        <span class="text-xs text-gray-400">No image</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Image -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Background Image</label>

                                <div class="mb-3">
                                    <label class="block text-xs font-medium text-gray-500 mb-1">Image source</label>
                                    <select id="image-source-mode" onchange="switchImageMode()" class="w-full max-w-xs px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary text-sm">
                                        <option value="upload">Upload from file</option>
                                        <option value="url">Enter image URL</option>
                                    </select>
                                </div>

                                <div id="image-preview-container" class="mb-3 <?php echo trim($settings['login_bg_image']) === '' ? 'hidden' : ''; ?>">
                                    <img id="image-preview" src="<?php echo $s('login_bg_image'); ?>" alt="Preview" class="max-w-full h-48 object-contain border border-gray-300 rounded-lg p-2 bg-gray-50">
                                    <button type="button" onclick="clearImagePreview()" class="mt-2 text-sm text-red-600 hover:text-red-800">
                                        <i class="fas fa-times mr-1"></i>Remove Image
                                    </button>
                                </div>

                                <div id="file-picker-block" class="border-2 border-dashed border-gray-300 rounded-lg p-4 mb-3">
                                    <div class="text-center">
                                        <input type="file" id="image-file-input" accept="image/jpeg,image/png,image/gif,image/webp" class="hidden">
                                        <label for="image-file-input" class="cursor-pointer inline-flex items-center px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg transition-colors">
                                            <i class="fas fa-upload mr-2"></i>Choose Image File
                                        </label>
                                        <p class="mt-2 text-xs text-gray-500">JPEG, PNG, GIF, or WebP (Max 10MB) — uploads automatically. Recommended: wide landscape images (≥1600px).</p>
                                    </div>
                                    <div id="upload-progress" class="hidden mt-2">
                                        <div class="bg-gray-200 rounded-full h-2">
                                            <div id="upload-progress-bar" class="bg-primary h-2 rounded-full transition-all" style="width: 0%"></div>
                                        </div>
                                        <p id="upload-status" class="text-sm text-gray-600 mt-1"></p>
                                    </div>
                                </div>

                                <div id="url-input-block" class="hidden mb-3">
                                    <input type="url" id="image-url-visible" value="<?php echo $s('login_bg_image'); ?>" class="field" placeholder="https://example.com/image.jpg" oninput="syncUrlInput(this.value)">
                                    <p class="mt-1 text-xs text-gray-500">Paste a full image URL (https://...).</p>
                                </div>

                                <input type="hidden" name="login_bg_image" id="image-url-input" value="<?php echo $s('login_bg_image'); ?>">
                            </div>

                            <!-- Overlay opacity -->
                            <div id="overlay-block">
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Image Visibility <span class="text-gray-400 font-normal">(overlay opacity)</span>
                                </label>
                                <div class="flex items-center gap-3">
                                    <input type="range" name="login_bg_overlay" id="overlay_opacity" min="0" max="100" step="1"
                                           value="<?php echo htmlspecialchars($settings['login_bg_overlay']); ?>"
                                           oninput="updateOpacityDisplay(this.value)"
                                           class="w-full max-w-xs accent-primary">
                                    <span id="overlay_opacity_value" class="text-sm font-medium text-gray-700 w-12 text-right"><?php echo htmlspecialchars($settings['login_bg_overlay']); ?>%</span>
                                </div>
                                <p class="text-xs text-gray-400 mt-1"><strong>0%</strong> = image fully visible, <strong>100%</strong> = fully covered by dark overlay. Default 80%.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Section: Logo -->
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <h2 class="text-xl font-bold mb-1 flex items-center gap-2"><i class="fas fa-certificate text-primary"></i> Logo</h2>
                        <p class="text-sm text-gray-500 mb-4">Shown in the circles on both panels.</p>

                        <div class="mb-4">
                            <label class="field-label" for="login_logo_mode">Edit logos</label>
                            <select name="login_logo_mode" id="login_logo_mode" onchange="updateLogoMode()" class="w-full max-w-xs px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary text-sm">
                                <option value="both" <?php echo $settings['login_logo_mode'] !== 'separate' ? 'selected' : ''; ?>>Same logo for both panels</option>
                                <option value="separate" <?php echo $settings['login_logo_mode'] === 'separate' ? 'selected' : ''; ?>>Edit each panel separately</option>
                            </select>
                        </div>

                        <!-- Shared / brand panel logo -->
                        <div>
                            <label class="field-label" id="login_logo_label">Logo</label>
                            <div class="flex items-center gap-4">
                                <img id="logo-preview" src="<?php echo $s('login_logo'); ?>" alt="Logo preview" class="h-14 w-14 object-contain border border-gray-200 rounded-full bg-white p-1 flex-shrink-0">
                                <div class="flex-1">
                                    <input type="text" name="login_logo" id="login_logo" value="<?php echo $s('login_logo'); ?>" class="field" placeholder="../uploads/images/tupi_supreme_logo.png" oninput="updatePreview(); document.getElementById('logo-preview').src = this.value;">
                                    <p class="mt-1 text-xs text-gray-400">Image path or full URL.</p>
                                </div>
                                <label for="logo-file-input" class="cursor-pointer inline-flex items-center px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg transition-colors text-sm flex-shrink-0">
                                    <i class="fas fa-upload mr-2"></i>Upload
                                </label>
                                <input type="file" id="logo-file-input" accept="image/jpeg,image/png,image/gif,image/webp" class="hidden">
                            </div>
                            <p id="logo-upload-status" class="text-xs mt-2"></p>
                        </div>

                        <!-- Sign-in panel logo (separate mode only) -->
                        <div id="logo-alt-block" class="mt-5 pt-4 border-t border-gray-100 <?php echo $settings['login_logo_mode'] === 'separate' ? '' : 'hidden'; ?>">
                            <label class="field-label">Sign-in panel logo</label>
                            <div class="flex items-center gap-4">
                                <img id="logo-alt-preview" src="<?php echo $s('login_logo_alt'); ?>" alt="Sign-in logo preview" class="h-14 w-14 object-contain border border-gray-200 rounded-full bg-white p-1 flex-shrink-0">
                                <div class="flex-1">
                                    <input type="text" name="login_logo_alt" id="login_logo_alt" value="<?php echo $s('login_logo_alt'); ?>" class="field" placeholder="../uploads/images/tupi_supreme_logo.png" oninput="updatePreview(); document.getElementById('logo-alt-preview').src = this.value;">
                                    <p class="mt-1 text-xs text-gray-400">Image path or full URL. Empty = falls back to the brand panel logo.</p>
                                </div>
                                <label for="logo-alt-file-input" class="cursor-pointer inline-flex items-center px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg transition-colors text-sm flex-shrink-0">
                                    <i class="fas fa-upload mr-2"></i>Upload
                                </label>
                                <input type="file" id="logo-alt-file-input" accept="image/jpeg,image/png,image/gif,image/webp" class="hidden">
                            </div>
                            <p id="logo-alt-upload-status" class="text-xs mt-2"></p>
                        </div>
                    </div>

                    <!-- Section: Brand panel content -->
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <h2 class="text-xl font-bold mb-1 flex items-center gap-2"><i class="fas fa-pen text-primary"></i> Brand Panel Content</h2>
                        <p class="text-sm text-gray-500 mb-4">Text on the dark left panel. Leave a field empty to hide it.</p>

                        <div class="space-y-4">
                            <div>
                                <label class="field-label" for="login_brand_line">Top line</label>
                                <input type="text" name="login_brand_line" id="login_brand_line" value="<?php echo $s('login_brand_line'); ?>" class="field" oninput="updatePreview()">
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="field-label" for="login_brand_eyebrow">Eyebrow (small label)</label>
                                    <input type="text" name="login_brand_eyebrow" id="login_brand_eyebrow" value="<?php echo $s('login_brand_eyebrow'); ?>" class="field" oninput="updatePreview()">
                                </div>
                                <div>
                                    <label class="field-label" for="login_accent_color">Accent color</label>
                                    <div class="flex items-center gap-2">
                                        <input type="color" name="login_accent_color" id="login_accent_color" value="<?php echo $s('login_accent_color'); ?>" class="h-9 w-14 rounded border border-gray-300 cursor-pointer bg-white" oninput="updatePreview()">
                                        <span class="text-xs text-gray-400">Headline highlight &amp; pill icons</span>
                                    </div>
                                </div>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="field-label" for="login_brand_title">Headline (line 1)</label>
                                    <input type="text" name="login_brand_title" id="login_brand_title" value="<?php echo $s('login_brand_title'); ?>" class="field" oninput="updatePreview()">
                                </div>
                                <div>
                                    <label class="field-label" for="login_brand_title_accent">Headline (accented line 2)</label>
                                    <input type="text" name="login_brand_title_accent" id="login_brand_title_accent" value="<?php echo $s('login_brand_title_accent'); ?>" class="field" oninput="updatePreview()">
                                </div>
                            </div>
                            <div>
                                <label class="field-label" for="login_brand_description">Description paragraph</label>
                                <textarea name="login_brand_description" id="login_brand_description" rows="2" class="field" oninput="updatePreview()"><?php echo $s('login_brand_description'); ?></textarea>
                            </div>

                            <div>
                                <label class="field-label">Feature pills <span class="text-gray-400 font-normal">(icon class + text; empty text hides the pill)</span></label>
                                <div class="space-y-2">
                                    <?php for ($i = 1; $i <= 3; $i++): ?>
                                        <div class="flex items-center gap-2">
                                            <input type="text" name="login_pill_<?php echo $i; ?>_icon" id="login_pill_<?php echo $i; ?>_icon" value="<?php echo $s("login_pill_{$i}_icon"); ?>" class="field w-36 font-mono text-xs" placeholder="fa-star" oninput="updatePreview()">
                                            <input type="text" name="login_pill_<?php echo $i; ?>_text" id="login_pill_<?php echo $i; ?>_text" value="<?php echo $s("login_pill_{$i}_text"); ?>" class="field" placeholder="Pill <?php echo $i; ?> text" oninput="updatePreview()">
                                        </div>
                                    <?php endfor; ?>
                                </div>
                                <p class="mt-1 text-xs text-gray-400">Icons are Font Awesome classes, e.g. <code>fa-leaf</code>, <code>fa-certificate</code>, <code>fa-envelope</code>.</p>
                            </div>

                            <div>
                                <label class="field-label" for="login_brand_quote">Bottom quote</label>
                                <textarea name="login_brand_quote" id="login_brand_quote" rows="2" class="field" oninput="updatePreview()"><?php echo $s('login_brand_quote'); ?></textarea>
                            </div>
                            <div>
                                <label class="field-label" for="login_brand_footer">Footer line</label>
                                <input type="text" name="login_brand_footer" id="login_brand_footer" value="<?php echo $s('login_brand_footer'); ?>" class="field" oninput="updatePreview()">
                            </div>
                        </div>
                    </div>

                    <!-- Section: Sign-in panel -->
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <h2 class="text-xl font-bold mb-1 flex items-center gap-2"><i class="fas fa-lock text-primary"></i> Sign-in Panel</h2>
                        <p class="text-sm text-gray-500 mb-4">The light panel containing the login form.</p>

                        <div class="space-y-4">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="field-label" for="login_form_eyebrow">Eyebrow (small label)</label>
                                    <input type="text" name="login_form_eyebrow" id="login_form_eyebrow" value="<?php echo $s('login_form_eyebrow'); ?>" class="field" oninput="updatePreview()">
                                </div>
                                <div>
                                    <label class="field-label" for="login_form_heading">Heading</label>
                                    <input type="text" name="login_form_heading" id="login_form_heading" value="<?php echo $s('login_form_heading'); ?>" class="field" oninput="updatePreview()">
                                </div>
                            </div>
                            <div>
                                <label class="field-label" for="login_form_subtext">Subtext</label>
                                <input type="text" name="login_form_subtext" id="login_form_subtext" value="<?php echo $s('login_form_subtext'); ?>" class="field" oninput="updatePreview()">
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="field-label" for="login_button_text">Button text</label>
                                    <input type="text" name="login_button_text" id="login_button_text" value="<?php echo $s('login_button_text'); ?>" class="field" oninput="updatePreview()">
                                </div>
                                <div>
                                    <label class="field-label" for="login_button_color">Button color</label>
                                    <div class="flex items-center gap-2">
                                        <input type="color" name="login_button_color" id="login_button_color" value="<?php echo $s('login_button_color'); ?>" class="h-9 w-14 rounded border border-gray-300 cursor-pointer bg-white" oninput="updatePreview()">
                                    </div>
                                </div>
                            </div>
                            <div>
                                <label class="field-label" for="login_form_bg_color">Panel background</label>
                                <div class="flex items-center gap-2">
                                    <input type="color" name="login_form_bg_color" id="login_form_bg_color" value="<?php echo $s('login_form_bg_color'); ?>" class="h-9 w-14 rounded border border-gray-300 cursor-pointer bg-white" oninput="updatePreview()">
                                    <span class="text-xs text-gray-400">Background behind the form</span>
                                </div>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <div>
                                    <label class="field-label" for="login_help_text">Help text</label>
                                    <input type="text" name="login_help_text" id="login_help_text" value="<?php echo $s('login_help_text'); ?>" class="field" oninput="updatePreview()">
                                </div>
                                <div>
                                    <label class="field-label" for="login_help_link_text">Help link text</label>
                                    <input type="text" name="login_help_link_text" id="login_help_link_text" value="<?php echo $s('login_help_link_text'); ?>" class="field" oninput="updatePreview()">
                                </div>
                                <div>
                                    <label class="field-label" for="login_help_link_url">Help link URL</label>
                                    <input type="text" name="login_help_link_url" id="login_help_link_url" value="<?php echo $s('login_help_link_url'); ?>" class="field" placeholder="../contact.php" oninput="updatePreview()">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col sm:flex-row gap-3">
                        <button type="submit" class="bg-primary text-white px-6 py-2.5 rounded-lg hover:bg-secondary transition-colors inline-flex items-center justify-center">
                            <i class="fas fa-save mr-2"></i>Save Login Page
                        </button>
                        <a href="index.php" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-6 py-2.5 rounded-lg transition-colors inline-flex items-center justify-center">
                            <i class="fas fa-times mr-2"></i>Cancel
                        </a>
                    </div>
                </div>

                <!-- Right: live preview -->
                <div class="xl:sticky xl:top-20 self-start">
                    <p class="text-sm font-medium text-gray-700 mb-2 flex items-center gap-2">
                        <i class="fas fa-eye text-gray-400"></i> Live Preview
                        <span class="text-xs text-gray-400 font-normal">(simplified)</span>
                    </p>
                    <div class="rounded-xl overflow-hidden border border-gray-200 shadow-sm bg-white">
                        <div class="flex" style="height: 460px;">
                            <!-- Brand panel replica -->
                            <div id="pv_brand" class="bg-preview w-[62%] flex flex-col justify-between p-4 text-white">
                                <p id="pv_brand_line" class="text-[8px] font-semibold text-white/90 leading-snug"></p>
                                <div class="text-center px-2">
                                    <div class="w-10 h-10 mx-auto rounded-full bg-white/95 flex items-center justify-center ring-2 ring-white/20">
                                        <img id="pv_logo" src="<?php echo $s('login_logo'); ?>" alt="" class="w-7 h-7 object-contain">
                                    </div>
                                    <p id="pv_brand_eyebrow" class="mt-2 text-[7px] font-semibold uppercase tracking-[0.2em] text-[#9fd4a8]"></p>
                                    <p class="mt-1 text-sm font-bold leading-snug"><span id="pv_brand_title"></span><br><span id="pv_brand_title_accent"></span></p>
                                    <p id="pv_brand_description" class="mt-1.5 text-[7px] text-white/75 leading-relaxed"></p>
                                    <div class="mt-2 flex flex-wrap justify-center gap-1">
                                        <?php for ($i = 1; $i <= 3; $i++): ?>
                                            <span id="pv_pill_<?php echo $i; ?>" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full border border-white/25 bg-white/10 text-[7px]">
                                                <i id="pv_pill_<?php echo $i; ?>_icon" class="fas"></i><span id="pv_pill_<?php echo $i; ?>_text"></span>
                                            </span>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                                <div class="text-center">
                                    <p id="pv_brand_quote" class="text-[7px] italic text-white/60 leading-snug"></p>
                                    <p id="pv_brand_footer" class="mt-1 text-[7px] text-white/50"></p>
                                </div>
                            </div>
                            <!-- Form panel replica -->
                            <div id="pv_form" class="w-[38%] flex flex-col items-center justify-center p-4 text-center">
                                <div class="w-9 h-9 rounded-full bg-white shadow ring-2 ring-[#e2eae4] flex items-center justify-center">
                                    <img id="pv_logo2" src="<?php echo $s('login_logo'); ?>" alt="" class="w-6 h-6 object-contain">
                                </div>
                                <p id="pv_form_eyebrow" class="mt-2 text-[7px] font-semibold uppercase tracking-[0.2em] text-[#3d7a66]"></p>
                                <p id="pv_form_heading" class="text-sm font-bold text-[#23332c] leading-tight"></p>
                                <p id="pv_form_subtext" class="text-[8px] text-[#7d8b84]"></p>
                                <div class="mt-3 w-full space-y-1.5">
                                    <div class="h-6 rounded-md bg-white border border-[#e2eae4]"></div>
                                    <div class="h-6 rounded-md bg-white border border-[#e2eae4]"></div>
                                    <div id="pv_button" class="h-7 rounded-md flex items-center justify-center text-[8px] font-semibold text-white"><span id="pv_button_text"></span></div>
                                </div>
                                <p class="mt-2 text-[7px] text-[#8a978f]"><span id="pv_help_text"></span> <span id="pv_help_link" class="font-medium"></span></p>
                            </div>
                        </div>
                    </div>
                    <p class="text-xs text-gray-400 mt-2">Approximate replica — open the <a href="<?php echo $view_url; ?>" target="_blank" class="underline">login page</a> for the real thing.</p>
                </div>
            </div>
        </form>
    </div>

    <script>
    // ---------- Live preview ----------
    function val(id) {
        var el = document.getElementById(id);
        return el ? el.value.trim() : '';
    }
    function setText(id, txt) {
        var el = document.getElementById(id);
        if (el) { el.textContent = txt; }
    }

    function updatePreview() {
        var enabled = document.getElementById('login_bg_enabled').checked;
        var imgUrl = val('image-url-input');
        var alpha = (parseInt(val('overlay_opacity') || '80', 10) / 100).toFixed(2);
        var brand = document.getElementById('pv_brand');
        var accent = val('login_accent_color') || '#8bc34a';

        brand.style.backgroundColor = val('login_bg_color');
        brand.style.backgroundImage = (enabled && imgUrl)
            ? "linear-gradient(rgba(21, 46, 30, " + alpha + "), rgba(21, 46, 30, " + alpha + ")), url('" + imgUrl + "')"
            : "linear-gradient(155deg, " + (val('login_bg_gradient_from') || '#2c5530') + " 0%, " + (val('login_bg_gradient_to') || '#152e1e') + " 100%)";

        var separateLogos = document.getElementById('login_logo_mode').value === 'separate';
        var logo = val('login_logo');
        var logoAlt = separateLogos ? (val('login_logo_alt') || logo) : logo;
        var pvLogo = document.getElementById('pv_logo');
        if (logo) { pvLogo.src = logo; pvLogo.style.display = ''; }
        else { pvLogo.style.display = 'none'; }
        var pvLogo2 = document.getElementById('pv_logo2');
        if (logoAlt) { pvLogo2.src = logoAlt; pvLogo2.style.display = ''; }
        else { pvLogo2.style.display = 'none'; }

        setText('pv_brand_line', val('login_brand_line'));
        setText('pv_brand_eyebrow', val('login_brand_eyebrow'));
        setText('pv_brand_title', val('login_brand_title'));
        var accentEl = document.getElementById('pv_brand_title_accent');
        accentEl.textContent = val('login_brand_title_accent');
        accentEl.style.color = accent;
        setText('pv_brand_description', val('login_brand_description'));

        for (var i = 1; i <= 3; i++) {
            var pill = document.getElementById('pv_pill_' + i);
            var text = val('login_pill_' + i + '_text');
            if (text === '') {
                pill.style.display = 'none';
            } else {
                pill.style.display = '';
                setText('pv_pill_' + i + '_text', text);
                var icon = document.getElementById('pv_pill_' + i + '_icon');
                icon.className = 'fas ' + (val('login_pill_' + i + '_icon') || 'fa-circle');
                icon.style.color = accent;
            }
        }

        var quote = val('login_brand_quote');
        setText('pv_brand_quote', quote ? '\u201C' + quote + '\u201D' : '');
        setText('pv_brand_footer', val('login_brand_footer'));

        document.getElementById('pv_form').style.backgroundColor = val('login_form_bg_color');
        setText('pv_form_eyebrow', val('login_form_eyebrow'));
        setText('pv_form_heading', val('login_form_heading'));
        setText('pv_form_subtext', val('login_form_subtext'));
        document.getElementById('pv_button').style.backgroundColor = val('login_button_color');
        setText('pv_button_text', val('login_button_text'));
        setText('pv_help_text', val('login_help_text'));
        var helpLink = document.getElementById('pv_help_link');
        helpLink.textContent = val('login_help_link_text');
        helpLink.style.color = val('login_button_color');
    }

    // Slider readout + live preview refresh
    function updateOpacityDisplay(value) {
        document.getElementById('overlay_opacity_value').textContent = value + '%';
        updatePreview();
    }

    // ---------- Background image picker ----------
    let selectedFile = null;
    let uploadState = 'idle';
    let imageMode = 'upload';

    function switchImageMode() {
        const mode = document.getElementById('image-source-mode').value;
        imageMode = mode;
        const fileBlock = document.getElementById('file-picker-block');
        const urlBlock = document.getElementById('url-input-block');
        if (mode === 'url') {
            fileBlock.classList.add('hidden');
            urlBlock.classList.remove('hidden');
            document.getElementById('image-url-visible').value = document.getElementById('image-url-input').value;
        } else {
            urlBlock.classList.add('hidden');
            fileBlock.classList.remove('hidden');
            if (uploadState === 'failed') {
                uploadState = 'idle';
                document.getElementById('upload-progress').classList.add('hidden');
            }
        }
    }

    // Keep the hidden submitted field in sync with the visible URL text box
    function syncUrlInput(value) {
        document.getElementById('image-url-input').value = value;
        const preview = document.getElementById('image-preview');
        if (value.trim()) {
            preview.src = value;
            preview.onerror = function() { this.style.display = 'none'; };
            preview.onload = function() {
                this.style.display = 'block';
                document.getElementById('image-preview-container').classList.remove('hidden');
            };
        } else {
            document.getElementById('image-preview-container').classList.add('hidden');
        }
        updatePreview();
    }

    document.getElementById('image-file-input').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            selectedFile = file;
            uploadState = 'idle';
            const reader = new FileReader();
            reader.onload = function(e) {
                const preview = document.getElementById('image-preview');
                preview.src = e.target.result;
                document.getElementById('image-preview-container').classList.remove('hidden');
            };
            reader.readAsDataURL(file);
            uploadImage();
        }
    });

    function uploadImage() {
        if (!selectedFile) { alert('Please select an image file first'); return; }
        const formData = new FormData();
        formData.append('image', selectedFile);
        const progressContainer = document.getElementById('upload-progress');
        const progressBar = document.getElementById('upload-progress-bar');
        const statusText = document.getElementById('upload-status');
        const fileLabel = document.querySelector('label[for="image-file-input"]');
        uploadState = 'uploading';
        progressContainer.classList.remove('hidden');
        statusText.textContent = 'Uploading...';
        statusText.classList.remove('text-green-600', 'text-red-600');
        progressBar.classList.remove('bg-green-500');
        progressBar.style.width = '0%';
        fileLabel.style.pointerEvents = 'none';
        fileLabel.style.opacity = '0.6';
        const xhr = new XMLHttpRequest();
        xhr.upload.addEventListener('progress', function(e) {
            if (e.lengthComputable) { progressBar.style.width = ((e.loaded / e.total) * 100) + '%'; }
        });
        xhr.addEventListener('load', function() {
            if (xhr.status === 200) {
                let response;
                try { response = JSON.parse(xhr.responseText); }
                catch (_) { response = { success: false, error: 'Unexpected server response' }; }
                if (response.success) {
                    document.getElementById('image-url-input').value = response.url;
                    statusText.textContent = 'Upload successful!';
                    statusText.classList.add('text-green-600');
                    progressBar.classList.add('bg-green-500');
                    uploadState = 'success';
                    setTimeout(() => { progressContainer.classList.add('hidden'); }, 2000);
                    updatePreview();
                } else {
                    statusText.textContent = 'Upload failed: ' + response.error;
                    statusText.classList.add('text-red-600');
                    uploadState = 'failed';
                }
            } else {
                let errMsg = 'Server error';
                try { errMsg = JSON.parse(xhr.responseText).error || errMsg; } catch (_) {}
                statusText.textContent = 'Upload failed: ' + errMsg;
                statusText.classList.add('text-red-600');
                uploadState = 'failed';
            }
            fileLabel.style.pointerEvents = '';
            fileLabel.style.opacity = '';
        });
        xhr.addEventListener('error', function() {
            statusText.textContent = 'Upload failed: Network error';
            statusText.classList.add('text-red-600');
            uploadState = 'failed';
            fileLabel.style.pointerEvents = '';
            fileLabel.style.opacity = '';
        });
        xhr.open('POST', 'api/upload_image.php');
        xhr.send(formData);
    }

    function clearImagePreview() {
        document.getElementById('image-preview-container').classList.add('hidden');
        document.getElementById('image-url-input').value = '';
        document.getElementById('image-url-visible').value = '';
        document.getElementById('image-file-input').value = '';
        selectedFile = null;
        uploadState = 'idle';
        document.getElementById('upload-progress').classList.add('hidden');
        updatePreview();
    }

    // ---------- Logo pickers ----------
    // Toggle between one shared logo and per-panel logos
    function updateLogoMode() {
        var separate = document.getElementById('login_logo_mode').value === 'separate';
        document.getElementById('logo-alt-block').classList.toggle('hidden', !separate);
        document.getElementById('login_logo_label').textContent = separate ? 'Brand panel logo' : 'Logo';
        updatePreview();
    }

    function wireLogoUpload(fileInputId, targetInputId, previewId, statusId) {
        document.getElementById(fileInputId).addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (!file) return;
            const status = document.getElementById(statusId);
            const formData = new FormData();
            formData.append('image', file);
            status.textContent = 'Uploading...';
            status.className = 'text-xs mt-2 text-gray-500';
            const xhr = new XMLHttpRequest();
            xhr.addEventListener('load', function() {
                let response = { success: false, error: 'Server error' };
                try { response = JSON.parse(xhr.responseText); } catch (_) {}
                if (xhr.status === 200 && response.success) {
                    document.getElementById(targetInputId).value = response.url;
                    document.getElementById(previewId).src = response.url;
                    status.textContent = 'Upload successful!';
                    status.className = 'text-xs mt-2 text-green-600';
                    updatePreview();
                } else {
                    status.textContent = 'Upload failed: ' + (response.error || 'error');
                    status.className = 'text-xs mt-2 text-red-600';
                }
            });
            xhr.addEventListener('error', function() {
                status.textContent = 'Upload failed: Network error';
                status.className = 'text-xs mt-2 text-red-600';
            });
            xhr.open('POST', 'api/upload_image.php');
            xhr.send(formData);
        });
    }
    wireLogoUpload('logo-file-input', 'login_logo', 'logo-preview', 'logo-upload-status');
    wireLogoUpload('logo-alt-file-input', 'login_logo_alt', 'logo-alt-preview', 'logo-alt-upload-status');

    function validateImageUpload() {
        const urlInput = document.getElementById('image-url-input');
        const enabled = document.getElementById('login_bg_enabled').checked;
        // Saving with the feature off is always fine — no image needed
        if (!enabled) { return true; }
        if (imageMode === 'url') {
            if (!urlInput.value.trim()) { alert('Please enter an image URL, or turn off "Use background image".'); return false; }
            return true;
        }
        if (uploadState === 'uploading') { alert('Please wait for the image upload to finish before saving.'); return false; }
        if (selectedFile && uploadState === 'failed') {
            // A failed upload must not block saving the rest of the settings —
            // if an image was already saved before, keep it and continue.
            if (urlInput.value.trim()) {
                if (!confirm('The new image failed to upload. Save the other settings and keep the current background image?')) { return false; }
            } else {
                alert('The image upload failed. Please try again, pick a smaller file, or turn off "Use background image" to save without one.');
                return false;
            }
        }
        if (!urlInput.value.trim()) { alert('Please choose and upload an image file first, or turn off "Use background image".'); return false; }
        return true;
    }

    // Initial paint
    updateLogoMode();
    </script>
</body>
</html>
