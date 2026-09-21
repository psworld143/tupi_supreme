<?php
require_once 'config.php';
requireLogin();

$db = getDB();
$error = '';
$success = '';

// Status flash from PRG redirect (avoids form resubmission on refresh)
if (($_GET['status'] ?? '') === 'saved') {
    $success = 'Login background saved successfully!';
}

// Load current settings (defaults: disabled, no image, 80% overlay)
$settings = [
    'login_bg_enabled'  => '0',
    'login_bg_image'    => '',
    'login_bg_overlay'  => '80',
    'login_bg_gradient' => '1',
    'login_bg_color'    => '#f5f7f5',
];
$res = $db->query("SELECT setting_key, setting_value FROM site_settings WHERE setting_key IN ('login_bg_enabled','login_bg_image','login_bg_overlay','login_bg_gradient','login_bg_color')");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
}

// Handle save (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken()) {
        $error = 'Security token expired or invalid. Please reload the page and try again.';
    } else {
        $enabled = isset($_POST['login_bg_enabled']) ? '1' : '0';
        $image = sanitizeInput($_POST['login_bg_image'] ?? '');
        $overlay = (string) max(0, min(100, intval($_POST['login_bg_overlay'] ?? 80)));
        $gradient = isset($_POST['login_bg_gradient']) ? '1' : '0';
        $color = sanitizeInput($_POST['login_bg_color'] ?? '#f5f7f5');
        if (!preg_match('/^#[0-9a-fA-F]{6}$/', $color)) {
            $color = '#f5f7f5';
        }

        $pairs = [
            'login_bg_enabled'  => $enabled,
            'login_bg_image'    => $image,
            'login_bg_overlay'  => $overlay,
            'login_bg_gradient' => $gradient,
            'login_bg_color'    => $color,
        ];

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
            logActivity('update', 'site_settings', 0, 'Updated login page background settings');
            redirect('login-background.php?status=saved');
        }
    }

    // Repopulate form after failed save
    $settings['login_bg_enabled'] = $enabled;
    $settings['login_bg_image'] = $image;
    $settings['login_bg_overlay'] = $overlay;
    $settings['login_bg_gradient'] = $gradient;
    $settings['login_bg_color'] = $color;
}

$bg_enabled = $settings['login_bg_enabled'] === '1';
$bg_image = $settings['login_bg_image'];
$bg_overlay = (int) $settings['login_bg_overlay'];
$bg_gradient = $settings['login_bg_gradient'] === '1';
$bg_color = $settings['login_bg_color'];
$overlay_alpha = number_format($bg_overlay / 100, 2);

// Login page is the target of these settings
$view_url = 'login.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Background - <?php echo SITE_NAME; ?></title>
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
            .lg\:ml-64 { height: calc(100vh - 2rem) !important; overflow-y: auto !important; }
            .lg\:ml-64 { scrollbar-width: thin; scrollbar-color: #d2dcd5 transparent; }
            .lg\:ml-64::-webkit-scrollbar { width: 8px; }
            .lg\:ml-64::-webkit-scrollbar-track { background: transparent; }
            .lg\:ml-64::-webkit-scrollbar-thumb { background-color: #d2dcd5; border-radius: 4px; border: 2px solid transparent; background-clip: padding-box; }
            .lg\:ml-64::-webkit-scrollbar-thumb:hover { background-color: #c0ccc5; }
        }
        .bg-preview { background-size: cover; background-position: center; }
    </style>

    <!-- Main Content -->
    <div class="lg:ml-64 p-4 lg:p-8">
        <!-- Page Header -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3">
                <div>
                    <h1 class="text-3xl font-bold text-gray-800 flex items-center gap-3">
                        <i class="fas fa-image text-primary"></i> Login Background
                    </h1>
                    <p class="text-sm text-gray-500 mt-1">Customize the background image behind the admin login page.</p>
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

        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-2xl font-bold mb-1">Login Page Background</h2>
            <p class="text-sm text-gray-500 mb-4">Shown behind the login card on the <a href="<?php echo $view_url; ?>" target="_blank" class="underline hover:text-gray-700">admin login page</a>.</p>

            <!-- Info banner -->
            <div class="mb-6 p-3 rounded-lg bg-blue-50 border border-blue-200 flex items-start gap-2">
                <i class="fas fa-info-circle text-blue-500 mt-0.5"></i>
                <p class="text-sm text-blue-800">
                    When enabled, your image replaces the default green gradient as the page background. Turn off <strong>gradient design</strong> to show the image clean (or a flat dark background with no image). The <strong>Image Visibility</strong> slider controls how strongly the green gradient covers the image — the same behavior as the homepage carousel slides.
                </p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Left: form fields -->
                <div>
                    <form method="POST" action="login-background.php" onsubmit="return validateImageUpload()">
                        <?php echo csrfTokenField(); ?>
                        <div class="space-y-6">
                            <!-- Enable toggles -->
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
                                    <span class="ml-3 text-sm font-medium text-gray-700">Use background image <span class="text-gray-400 font-normal">(off = gradient or solid color only)</span></span>
                                </label>
                                <label class="flex items-center cursor-pointer select-none">
                                    <input type="checkbox" name="login_bg_gradient" id="login_bg_gradient" value="1"
                                           <?php echo $bg_gradient ? 'checked' : ''; ?>
                                           class="sr-only peer" onchange="updatePreview()">
                                    <span class="relative w-11 h-6 rounded-full bg-gray-300 transition-colors duration-300 ease-in-out
                                                 peer-checked:bg-primary peer-focus-visible:ring-2 peer-focus-visible:ring-primary/40 peer-focus-visible:ring-offset-2
                                                 after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:w-5 after:h-5
                                                 after:bg-white after:rounded-full after:shadow
                                                 after:transition-transform after:duration-300 after:ease-in-out
                                                 peer-checked:after:translate-x-5
                                                 hover:after:scale-110 active:after:scale-95"></span>
                                    <span class="ml-3 text-sm font-medium text-gray-700">Use gradient design <span class="text-gray-400 font-normal">(off = clean image / solid color)</span></span>
                                </label>
                                <div class="flex items-center gap-3 pl-14">
                                    <input type="color" name="login_bg_color" id="login_bg_color"
                                           value="<?php echo htmlspecialchars($bg_color); ?>"
                                           class="h-8 w-14 rounded border border-gray-300 cursor-pointer bg-white"
                                           oninput="updatePreview()">
                                    <span class="text-sm text-gray-600">Background color <span class="text-gray-400">(used when gradient is off)</span></span>
                                </div>
                            </div>

                            <!-- Image -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Background Image</label>

                                <!-- Image source mode selector -->
                                <div class="mb-3">
                                    <label class="block text-xs font-medium text-gray-500 mb-1">Image source</label>
                                    <select id="image-source-mode" onchange="switchImageMode()" class="w-full max-w-xs px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary text-sm">
                                        <option value="upload">Upload from file</option>
                                        <option value="url">Enter image URL</option>
                                    </select>
                                </div>

                                <!-- Image Preview -->
                                <div id="image-preview-container" class="mb-3 <?php echo empty($bg_image) ? 'hidden' : ''; ?>">
                                    <img id="image-preview" src="<?php echo htmlspecialchars($bg_image); ?>" alt="Preview" class="max-w-full h-48 object-contain border border-gray-300 rounded-lg p-2 bg-gray-50">
                                    <button type="button" onclick="clearImagePreview()" class="mt-2 text-sm text-red-600 hover:text-red-800">
                                        <i class="fas fa-times mr-1"></i>Remove Image
                                    </button>
                                </div>

                                <!-- File Picker (upload mode) -->
                                <div id="file-picker-block" class="border-2 border-dashed border-gray-300 rounded-lg p-4 mb-3">
                                    <div class="text-center">
                                        <input type="file" id="image-file-input" accept="image/jpeg,image/png,image/gif,image/webp" class="hidden">
                                        <label for="image-file-input" class="cursor-pointer inline-flex items-center px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg transition-colors">
                                            <i class="fas fa-upload mr-2"></i>Choose Image File
                                        </label>
                                        <p class="mt-2 text-xs text-gray-500">JPEG, PNG, GIF, or WebP (Max 10MB) — file uploads automatically when selected. Recommended: wide landscape images (≥1600px).</p>
                                    </div>
                                    <div id="upload-progress" class="hidden mt-2">
                                        <div class="bg-gray-200 rounded-full h-2">
                                            <div id="upload-progress-bar" class="bg-primary h-2 rounded-full transition-all" style="width: 0%"></div>
                                        </div>
                                        <p id="upload-status" class="text-sm text-gray-600 mt-1"></p>
                                    </div>
                                </div>

                                <!-- URL Input (url mode) -->
                                <div id="url-input-block" class="hidden mb-3">
                                    <input type="url" id="image-url-visible" value="<?php echo htmlspecialchars($bg_image); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary" placeholder="https://example.com/image.jpg" oninput="syncUrlInput(this.value)">
                                    <p class="mt-1 text-xs text-gray-500">Paste a full image URL (https://...).</p>
                                </div>

                                <!-- Hidden field: the actual value submitted with the form -->
                                <input type="hidden" name="login_bg_image" id="image-url-input" value="<?php echo htmlspecialchars($bg_image); ?>">
                            </div>

                            <!-- Image visibility: overlay opacity (same as carousel) -->
                            <div id="overlay-block" class="<?php echo $bg_gradient ? '' : 'opacity-40 pointer-events-none'; ?>">
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Image Visibility <span class="text-gray-400 font-normal">(overlay opacity — applies when gradient is on)</span>
                                </label>
                                <div class="flex items-center gap-3">
                                    <input type="range" name="login_bg_overlay" id="overlay_opacity" min="0" max="100" step="1"
                                           value="<?php echo htmlspecialchars($bg_overlay); ?>"
                                           oninput="updateOpacityDisplay(this.value)"
                                           class="w-full max-w-xs accent-primary">
                                    <span id="overlay_opacity_value" class="text-sm font-medium text-gray-700 w-12 text-right"><?php echo htmlspecialchars($bg_overlay); ?>%</span>
                                </div>
                                <p class="text-xs text-gray-400 mt-1">Controls how strongly the dark green gradient covers the image. <strong>0%</strong> = image fully visible, <strong>100%</strong> = image fully hidden behind the green overlay. Default 80%.</p>
                            </div>
                        </div>

                        <div class="mt-8 flex flex-col sm:flex-row gap-3">
                            <button type="submit" class="bg-primary text-white px-6 py-2.5 rounded-lg hover:bg-secondary transition-colors inline-flex items-center justify-center">
                                <i class="fas fa-save mr-2"></i>Save Background
                            </button>
                            <a href="index.php" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-6 py-2.5 rounded-lg transition-colors inline-flex items-center justify-center">
                                <i class="fas fa-times mr-2"></i>Cancel
                            </a>
                        </div>
                    </form>
                </div>

                <!-- Right: live preview -->
                <div>
                    <p class="text-sm font-medium text-gray-700 mb-2 flex items-center gap-2">
                        <i class="fas fa-eye text-gray-400"></i> Live Preview
                        <span class="text-xs text-gray-400 font-normal">(admin login page)</span>
                    </p>
                    <!-- Approximation of login.php -->
                    <div class="rounded-lg overflow-hidden border border-gray-200">
                        <div id="login_preview" class="bg-preview relative h-96 flex items-center justify-center p-6"
                             style="background-image: <?php
                                 if ($bg_enabled && $bg_image) {
                                     echo $bg_gradient
                                         ? "linear-gradient(135deg, rgba(35, 51, 44, {$overlay_alpha}), rgba(61, 122, 102, {$overlay_alpha})), url('" . htmlspecialchars($bg_image) . "')"
                                         : "url('" . htmlspecialchars($bg_image) . "')";
                                 } else {
                                     echo $bg_gradient ? "linear-gradient(135deg, #23332c, #3d7a66)" : "none";
                                 }
                             ?>;<?php echo (!$bg_enabled || !$bg_image) && !$bg_gradient ? ' background-color: ' . htmlspecialchars($bg_color) . ';' : ''; ?>">
                            <!-- Mini login card replica -->
                            <div class="rounded-2xl p-6 w-full max-w-[240px] text-center" style="background-color: rgba(255,255,255,0.95);">
                                <div id="preview_icon" class="w-12 h-12 rounded-xl mx-auto flex items-center justify-center" style="background: <?php echo $bg_gradient ? 'linear-gradient(135deg, #3d7a66, #60796e)' : '#3d7a66'; ?>;">
                                    <i class="fas fa-shield-alt text-white"></i>
                                </div>
                                <span id="preview_eyebrow" class="inline-block mt-3 px-2.5 py-1 text-[9px] font-semibold uppercase tracking-wider rounded-full" style="background-color: <?php echo $bg_gradient ? 'rgba(255,255,255,0.15)' : 'rgba(61,122,102,0.12)'; ?>; color: <?php echo $bg_gradient ? 'rgba(255,255,255,0.9)' : '#3d7a66'; ?>;">Secure Access</span>
                                <p class="text-sm font-bold mt-2" style="color: #23332c;">Admin Console Login</p>
                                <div class="mt-3 space-y-2">
                                    <div class="h-7 rounded-lg" style="background: #f7faf8; border: 1px solid #d6ded9;"></div>
                                    <div class="h-7 rounded-lg" style="background: #f7faf8; border: 1px solid #d6ded9;"></div>
                                    <div class="h-7 rounded-full" style="background: #23332c;"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <p class="text-xs text-gray-400 mt-2">Simplified replica — the real page also shows floating gradient orbs and a grain overlay when the gradient design is on.</p>
                </div>
            </div>
        </div>
    </div>

    <script>
    // ---------- Live preview ----------
    function updatePreview() {
        var enabled = document.getElementById('login_bg_enabled').checked;
        var gradient = document.getElementById('login_bg_gradient').checked;
        var imgUrl = document.getElementById('image-url-input').value;
        var opacity = document.getElementById('overlay_opacity').value;
        var alpha = (parseInt(opacity, 10) / 100).toFixed(2);
        var preview = document.getElementById('login_preview');
        var overlayBlock = document.getElementById('overlay-block');
        var bgColor = document.getElementById('login_bg_color').value;

        // Overlay slider only matters while the gradient overlay is on
        if (gradient) {
            overlayBlock.classList.remove('opacity-40', 'pointer-events-none');
        } else {
            overlayBlock.classList.add('opacity-40', 'pointer-events-none');
        }

        if (enabled && imgUrl.trim()) {
            preview.style.backgroundColor = gradient ? '' : bgColor;
            preview.style.backgroundImage = gradient
                ? "linear-gradient(135deg, rgba(35, 51, 44, " + alpha + "), rgba(61, 122, 102, " + alpha + ")), url('" + imgUrl + "')"
                : "url('" + imgUrl + "')";
        } else if (gradient) {
            preview.style.backgroundColor = '';
            preview.style.backgroundImage = "linear-gradient(135deg, #23332c, #3d7a66)";
        } else {
            preview.style.backgroundImage = "none";
            preview.style.backgroundColor = bgColor;
        }

        // Match the plain-mode element overrides on the real page
        document.getElementById('preview_icon').style.background = gradient
            ? 'linear-gradient(135deg, #3d7a66, #60796e)' : '#3d7a66';
        var eyebrow = document.getElementById('preview_eyebrow');
        eyebrow.style.backgroundColor = gradient ? 'rgba(255,255,255,0.15)' : 'rgba(61,122,102,0.12)';
        eyebrow.style.color = gradient ? 'rgba(255,255,255,0.9)' : '#3d7a66';
    }

    // Slider readout + live preview refresh
    function updateOpacityDisplay(value) {
        document.getElementById('overlay_opacity_value').textContent = value + '%';
        updatePreview();
    }

    // ---------- Image upload logic (same as carousel) ----------
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
                const response = JSON.parse(xhr.responseText);
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
        if (selectedFile && uploadState === 'failed') { alert('The image upload failed. Please try again or pick a different file.'); return false; }
        if (!urlInput.value.trim()) { alert('Please choose and upload an image file first, or turn off "Use background image".'); return false; }
        return true;
    }
    </script>
</body>
</html>
