<?php
require_once 'config.php';
requireLogin();

$db = getDB();
$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? null;
$error = '';
$success = '';

// Status flash from PRG redirect (avoids form resubmission on refresh)
$status_param = $_GET['status'] ?? '';
if ($status_param === 'saved')   $success = 'Testimonial saved successfully!';
if ($status_param === 'deleted') $success = 'Testimonial deleted successfully!';

// Handle form submissions (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['delete_id'] ?? '') === '') {
    if (!verifyCsrfToken()) {
        $error = 'Security token expired or invalid. Please reload the page and try again.';
    } else {
        $name = sanitizeInput($_POST['name'] ?? '');
        $position = sanitizeInput($_POST['position'] ?? '');
        $company = sanitizeInput($_POST['company'] ?? '');
        $testimonial = $_POST['testimonial'] ?? '';
        $photo_url = sanitizeInput($_POST['photo_url'] ?? '');
        $display_order = intval($_POST['display_order'] ?? 0);
        $is_active = isset($_POST['is_active']) ? 1 : 0;

        if (empty($name) || trim($testimonial) === '') {
            $error = 'Name and testimonial text are both required.';
        } else {
            if ($action === 'add') {
                $stmt = $db->prepare("INSERT INTO testimonials (name, position, company, testimonial, photo_url, display_order, is_active, updated_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sssssiii", $name, $position, $company, $testimonial, $photo_url, $display_order, $is_active, $_SESSION['admin_id']);
                if ($stmt->execute()) {
                    logActivity('create', 'testimonials', $db->insert_id, "Added testimonial: {$name}");
                    redirect('testimonials.php?status=saved');
                } else {
                    $error = 'Error: ' . $stmt->error;
                }
            } elseif ($action === 'edit' && $id) {
                $stmt = $db->prepare("UPDATE testimonials SET name = ?, position = ?, company = ?, testimonial = ?, photo_url = ?, display_order = ?, is_active = ?, updated_by = ? WHERE id = ?");
                $stmt->bind_param("sssssiiii", $name, $position, $company, $testimonial, $photo_url, $display_order, $is_active, $_SESSION['admin_id'], $id);
                if ($stmt->execute()) {
                    logActivity('update', 'testimonials', $id, "Updated testimonial: {$name}");
                    redirect('testimonials.php?status=saved');
                } else {
                    $error = 'Error: ' . $stmt->error;
                }
            }
        }
    }
}

// Handle delete (POST only — safer than GET)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['delete_id'] ?? '') !== '') {
    if (!verifyCsrfToken()) {
        $error = 'Security token expired or invalid. Please reload the page and try again.';
    } else {
        $del_id = intval($_POST['delete_id']);
        $stmt = $db->prepare("DELETE FROM testimonials WHERE id = ?");
        $stmt->bind_param("i", $del_id);
        if ($stmt->execute()) {
            logActivity('delete', 'testimonials', $del_id, 'Deleted testimonial');
            redirect('testimonials.php?status=deleted');
        } else {
            $error = 'Error deleting testimonial: ' . $stmt->error;
        }
    }
}

// Get testimonial for edit
$edit_testimonial = null;
if ($action === 'edit' && $id) {
    $stmt = $db->prepare("SELECT * FROM testimonials WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $edit_testimonial = $result->fetch_assoc();
    if (!$edit_testimonial) {
        $error = 'Testimonial not found.';
        $action = 'list';
    }
}

// Get all testimonials for list (with pagination + filtering + search)
$all_testimonials = [];
$total_testimonials = 0;
$total_pages = 1;
$current_page_num = 1;
$per_page = 10;

if ($action === 'list') {
    $current_page_num = max(1, intval($_GET['page'] ?? 1));
    $filter_status = $_GET['filter_status'] ?? '';
    $search_q = trim($_GET['q'] ?? '');

    // Build dynamic WHERE
    $where = [];
    $params = [];
    $types = '';
    if ($filter_status === 'active') {
        $where[] = 'is_active = 1';
    } elseif ($filter_status === 'inactive') {
        $where[] = 'is_active = 0';
    }
    if ($search_q !== '') {
        $where[] = '(name LIKE ? OR company LIKE ? OR testimonial LIKE ?)';
        $like = '%' . $search_q . '%';
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
        $types .= 'sss';
    }
    $where_sql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

    // Total count
    $count_stmt = $db->prepare("SELECT COUNT(*) as total FROM testimonials $where_sql");
    if ($types && $count_stmt) {
        $count_stmt->bind_param($types, ...$params);
    }
    if ($count_stmt) {
        $count_stmt->execute();
        $total_testimonials = $count_stmt->get_result()->fetch_assoc()['total'];
    }
    $total_pages = max(1, ceil($total_testimonials / $per_page));
    if ($current_page_num > $total_pages) {
        $current_page_num = $total_pages;
    }
    $offset = ($current_page_num - 1) * $per_page;

    // List query
    $list_stmt = $db->prepare("SELECT * FROM testimonials $where_sql ORDER BY display_order, id LIMIT ? OFFSET ?");
    $list_params = $params;
    $list_types = $types . 'ii';
    $list_params[] = $per_page;
    $list_params[] = $offset;
    if ($list_stmt) {
        $list_stmt->bind_param($list_types, ...$list_params);
        $list_stmt->execute();
        $result = $list_stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $all_testimonials[] = $row;
        }
    }
}

// Quick stats for the list header
$stats = ['total' => 0, 'active' => 0, 'inactive' => 0];
$st = $db->query("SELECT COUNT(*) total, SUM(is_active) active FROM testimonials");
if ($st) {
    $row = $st->fetch_assoc();
    $stats['total'] = (int)$row['total'];
    $stats['active'] = (int)$row['active'];
    $stats['inactive'] = $stats['total'] - $stats['active'];
}

// Testimonials render in the testimonials section on the public Services page
$view_url = '../services.php#testimonials';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Testimonials - <?php echo SITE_NAME; ?></title>
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
    </style>

    <!-- Main Content -->
    <div class="lg:ml-64 p-4 lg:p-8">
        <!-- Page Header -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3">
                <div>
                    <h1 class="text-3xl font-bold text-gray-800 flex items-center gap-3">
                        <i class="fas fa-quote-left text-primary"></i> Testimonials
                    </h1>
                    <p class="text-sm text-gray-500 mt-1">Manage the client testimonial cards shown on the Services page.</p>
                </div>
                <?php if ($action === 'list'): ?>
                <a href="?action=add" class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-secondary transition-colors inline-flex items-center justify-center">
                    <i class="fas fa-plus mr-2"></i>Add New Testimonial
                </a>
                <?php else: ?>
                <a href="testimonials.php" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200 transition-colors inline-flex items-center justify-center">
                    <i class="fas fa-arrow-left mr-2"></i>Back to List
                </a>
                <?php endif; ?>
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

        <?php if ($action === 'add' || $action === 'edit'): ?>
            <!-- Add/Edit Form -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-2xl font-bold mb-1"><?php echo $action === 'add' ? 'Add New' : 'Edit'; ?> Testimonial</h2>
                <p class="text-sm text-gray-500 mb-4">Fields marked <span class="text-red-500">*</span> are required.</p>

                <!-- Info banner -->
                <div class="mb-6 p-3 rounded-lg bg-blue-50 border border-blue-200 flex items-start gap-2">
                    <i class="fas fa-info-circle text-blue-500 mt-0.5"></i>
                    <p class="text-sm text-blue-800">Testimonials appear as cards in the Client Testimonials section of the public <a href="<?php echo $view_url; ?>" target="_blank" class="underline hover:text-blue-900">Services page</a> — photo (or a placeholder avatar), name, position, company, and the quote. The section heading is editable under <a href="pages.php" class="underline hover:text-blue-900">Pages</a> (services → testimonials_title / testimonials_subtitle). Note: the public page shows only the <strong>first 3 active</strong> testimonials by display order.</p>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Left: form fields -->
                    <div>
                        <form method="POST" action="">
                            <?php echo csrfTokenField(); ?>
                            <div class="space-y-6">
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Name <span class="text-red-500">*</span></label>
                                        <input type="text" name="name" id="name_input" required maxlength="200"
                                               value="<?php echo htmlspecialchars($edit_testimonial['name'] ?? ''); ?>"
                                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary"
                                               placeholder="e.g., John Davis"
                                               oninput="updatePreview()">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Position</label>
                                        <input type="text" name="position" id="position_input" maxlength="200"
                                               value="<?php echo htmlspecialchars($edit_testimonial['position'] ?? ''); ?>"
                                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary"
                                               placeholder="e.g., Water Treatment Plant Manager"
                                               oninput="updatePreview()">
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Company <span class="text-gray-400 font-normal">(optional)</span></label>
                                    <input type="text" name="company" id="company_input" maxlength="200"
                                           value="<?php echo htmlspecialchars($edit_testimonial['company'] ?? ''); ?>"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary"
                                           placeholder="e.g., City Water Authority"
                                           oninput="updatePreview()">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Testimonial <span class="text-red-500">*</span></label>
                                    <textarea name="testimonial" id="testimonial_input" rows="4" required
                                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary"
                                              placeholder="e.g., The technical consultation services helped us optimize our water treatment system..."
                                              oninput="updatePreview()"><?php echo htmlspecialchars($edit_testimonial['testimonial'] ?? ''); ?></textarea>
                                    <p class="text-xs text-gray-400 mt-1">The quote text — shown in quotation marks on the card.</p>
                                </div>

                                <!-- Photo -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Photo <span class="text-gray-400 font-normal">(optional)</span></label>
                                    <div class="mb-3">
                                        <label class="block text-xs font-medium text-gray-500 mb-1">Image source</label>
                                        <select id="image-source-mode" onchange="switchImageMode()" class="w-full max-w-xs px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary text-sm">
                                            <option value="upload">Upload from file</option>
                                            <option value="url">Enter image URL</option>
                                        </select>
                                    </div>

                                    <div id="image-preview-container" class="mb-3 <?php echo empty($edit_testimonial['photo_url'] ?? '') ? 'hidden' : ''; ?>">
                                        <img id="image-preview" src="<?php echo htmlspecialchars($edit_testimonial['photo_url'] ?? ''); ?>" alt="Preview" class="w-16 h-16 object-cover border-4 border-[#eef3f0] rounded-full bg-gray-50">
                                        <button type="button" onclick="clearImagePreview()" class="mt-2 text-sm text-red-600 hover:text-red-800 block"><i class="fas fa-times mr-1"></i>Remove Photo</button>
                                    </div>

                                    <div id="file-picker-block" class="border-2 border-dashed border-gray-300 rounded-lg p-4 mb-3">
                                        <div class="text-center">
                                            <input type="file" id="image-file-input" accept="image/jpeg,image/png,image/gif,image/webp" class="hidden">
                                            <label for="image-file-input" class="cursor-pointer inline-flex items-center px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg transition-colors">
                                                <i class="fas fa-upload mr-2"></i>Choose Photo
                                            </label>
                                            <p class="mt-2 text-xs text-gray-500">JPEG, PNG, GIF, or WebP (Max 10MB). Recommended: square headshot.</p>
                                        </div>
                                        <div id="upload-progress" class="hidden mt-2">
                                            <div class="bg-gray-200 rounded-full h-2">
                                                <div id="upload-progress-bar" class="bg-primary h-2 rounded-full transition-all" style="width: 0%"></div>
                                            </div>
                                            <p id="upload-status" class="text-sm text-gray-600 mt-1"></p>
                                        </div>
                                    </div>

                                    <div id="url-input-block" class="hidden mb-3">
                                        <input type="url" id="image-url-visible" value="<?php echo htmlspecialchars($edit_testimonial['photo_url'] ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary" placeholder="https://example.com/photo.jpg" oninput="syncImageUrl(this.value)">
                                        <p class="mt-1 text-xs text-gray-500">Paste a full image URL (https://...).</p>
                                    </div>

                                    <input type="hidden" name="photo_url" id="image-url-input" value="<?php echo htmlspecialchars($edit_testimonial['photo_url'] ?? ''); ?>">
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Display Order</label>
                                        <input type="number" name="display_order" min="0"
                                               value="<?php echo htmlspecialchars($edit_testimonial['display_order'] ?? 0); ?>"
                                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary">
                                        <p class="text-xs text-gray-400 mt-1">Lower numbers appear first. Only the first 3 active show on the site.</p>
                                    </div>
                                    <div class="flex items-center">
                                        <label class="flex items-center cursor-pointer select-none">
                                            <input type="checkbox" name="is_active" value="1"
                                                   <?php echo ($edit_testimonial && $edit_testimonial['is_active']) || !$edit_testimonial ? 'checked' : ''; ?>
                                                   class="sr-only peer">
                                            <span class="relative w-11 h-6 rounded-full bg-gray-300 transition-colors duration-300 ease-in-out
                                                         peer-checked:bg-primary peer-focus-visible:ring-2 peer-focus-visible:ring-primary/40 peer-focus-visible:ring-offset-2
                                                         after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:w-5 after:h-5
                                                         after:bg-white after:rounded-full after:shadow
                                                         after:transition-transform after:duration-300 after:ease-in-out
                                                         peer-checked:after:translate-x-5
                                                         hover:after:scale-110 active:after:scale-95"></span>
                                            <span class="ml-3 text-sm text-gray-700">Active <span class="text-gray-400">(shown on the website)</span></span>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-8 flex flex-col sm:flex-row gap-3">
                                <button type="submit" class="bg-primary text-white px-6 py-2.5 rounded-lg hover:bg-secondary transition-colors inline-flex items-center justify-center">
                                    <i class="fas fa-save mr-2"></i><?php echo $action === 'add' ? 'Add Testimonial' : 'Save Changes'; ?>
                                </button>
                                <a href="testimonials.php" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-6 py-2.5 rounded-lg transition-colors inline-flex items-center justify-center">
                                    <i class="fas fa-times mr-2"></i>Cancel
                                </a>
                            </div>
                        </form>
                    </div>

                    <!-- Right: live preview -->
                    <div>
                        <p class="text-sm font-medium text-gray-700 mb-2 flex items-center gap-2">
                            <i class="fas fa-eye text-gray-400"></i> Live Preview
                            <span class="text-xs text-gray-400 font-normal">(testimonial card)</span>
                        </p>
                        <div class="bg-white border border-[#e6ece8] rounded-2xl p-8 max-w-sm">
                            <div class="text-center mb-4">
                                <img id="preview_photo" src="<?php echo htmlspecialchars($edit_testimonial['photo_url'] ?? ''); ?>" alt="" class="w-16 h-16 rounded-full mx-auto mb-4 object-cover border-4 border-[#eef3f0] <?php echo empty($edit_testimonial['photo_url'] ?? '') ? 'hidden' : ''; ?>">
                                <div id="preview_photo_fallback" class="w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4 border-4 border-[#eef3f0] <?php echo empty($edit_testimonial['photo_url'] ?? '') ? '' : 'hidden'; ?>" style="background: linear-gradient(135deg, #3d7a66, #60796e);">
                                    <i class="fas fa-user text-xl text-white"></i>
                                </div>
                                <h5 id="preview_name" class="text-lg font-semibold text-[#23332c] mb-1"><?php echo htmlspecialchars($edit_testimonial['name'] ?? 'Client name'); ?></h5>
                                <p id="preview_position" class="text-[#3d7a66] text-sm font-medium <?php echo empty($edit_testimonial['position'] ?? '') ? 'hidden' : ''; ?>"><?php echo htmlspecialchars($edit_testimonial['position'] ?? ''); ?></p>
                                <p id="preview_company" class="text-[#8a978f] text-xs mt-1 <?php echo empty($edit_testimonial['company'] ?? '') ? 'hidden' : ''; ?>"><?php echo htmlspecialchars($edit_testimonial['company'] ?? ''); ?></p>
                            </div>
                            <p class="text-center text-[#5a6b62] leading-relaxed text-sm">"<span id="preview_quote"><?php echo htmlspecialchars($edit_testimonial['testimonial'] ?? 'The testimonial text will appear here.'); ?></span>"</p>
                        </div>
                        <p class="text-xs text-gray-400 mt-2">The card style mirrors the testimonial cards on the public Services page.</p>
                    </div>
                </div>
            </div>

            <script>
            // Live preview
            function updatePreview() {
                var name = document.getElementById('name_input').value || 'Client name';
                var position = document.getElementById('position_input').value || '';
                var company = document.getElementById('company_input').value || '';
                var quote = document.getElementById('testimonial_input').value || 'The testimonial text will appear here.';
                var imgUrl = document.getElementById('image-url-input').value;

                document.getElementById('preview_name').textContent = name;
                document.getElementById('preview_quote').textContent = quote;

                var posEl = document.getElementById('preview_position');
                if (position.trim()) { posEl.textContent = position; posEl.classList.remove('hidden'); }
                else { posEl.classList.add('hidden'); }

                var compEl = document.getElementById('preview_company');
                if (company.trim()) { compEl.textContent = company; compEl.classList.remove('hidden'); }
                else { compEl.classList.add('hidden'); }

                var photoImg = document.getElementById('preview_photo');
                var photoFallback = document.getElementById('preview_photo_fallback');
                if (imgUrl.trim()) {
                    photoImg.src = imgUrl;
                    photoImg.classList.remove('hidden');
                    photoFallback.classList.add('hidden');
                } else {
                    photoImg.classList.add('hidden');
                    photoFallback.classList.remove('hidden');
                }
            }

            // ===== Photo upload =====
            let selectedImageFile = null;
            let imageUploadState = 'idle';

            function switchImageMode() {
                const mode = document.getElementById('image-source-mode').value;
                const fileBlock = document.getElementById('file-picker-block');
                const urlBlock = document.getElementById('url-input-block');
                if (mode === 'url') {
                    fileBlock.classList.add('hidden');
                    urlBlock.classList.remove('hidden');
                    document.getElementById('image-url-visible').value = document.getElementById('image-url-input').value;
                } else {
                    urlBlock.classList.add('hidden');
                    fileBlock.classList.remove('hidden');
                    if (imageUploadState === 'failed') {
                        imageUploadState = 'idle';
                        document.getElementById('upload-progress').classList.add('hidden');
                    }
                }
            }

            function syncImageUrl(value) {
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
                    selectedImageFile = file;
                    imageUploadState = 'idle';
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const preview = document.getElementById('image-preview');
                        preview.src = e.target.result;
                        document.getElementById('image-preview-container').classList.remove('hidden');
                    };
                    reader.readAsDataURL(file);
                    uploadImageFile();
                }
            });

            function uploadImageFile() {
                if (!selectedImageFile) { alert('Please select an image file first'); return; }
                const formData = new FormData();
                formData.append('image', selectedImageFile);
                const pc = document.getElementById('upload-progress');
                const pb = document.getElementById('upload-progress-bar');
                const st = document.getElementById('upload-status');
                const fl = document.querySelector('label[for="image-file-input"]');
                imageUploadState = 'uploading';
                pc.classList.remove('hidden');
                st.textContent = 'Uploading...';
                st.classList.remove('text-green-600', 'text-red-600');
                pb.style.width = '0%';
                fl.style.pointerEvents = 'none'; fl.style.opacity = '0.6';
                const xhr = new XMLHttpRequest();
                xhr.upload.addEventListener('progress', function(e) {
                    if (e.lengthComputable) { pb.style.width = ((e.loaded / e.total) * 100) + '%'; }
                });
                xhr.addEventListener('load', function() {
                    if (xhr.status === 200) {
                        const r = JSON.parse(xhr.responseText);
                        if (r.success) {
                            document.getElementById('image-url-input').value = r.url;
                            st.textContent = 'Upload successful!'; st.classList.add('text-green-600');
                            imageUploadState = 'success';
                            setTimeout(() => { pc.classList.add('hidden'); }, 2000);
                            updatePreview();
                        } else {
                            st.textContent = 'Upload failed: ' + r.error; st.classList.add('text-red-600'); imageUploadState = 'failed';
                        }
                    } else {
                        let m = 'Server error'; try { m = JSON.parse(xhr.responseText).error || m; } catch (_) {}
                        st.textContent = 'Upload failed: ' + m; st.classList.add('text-red-600'); imageUploadState = 'failed';
                    }
                    fl.style.pointerEvents = ''; fl.style.opacity = '';
                });
                xhr.addEventListener('error', function() {
                    st.textContent = 'Upload failed: Network error'; st.classList.add('text-red-600'); imageUploadState = 'failed';
                    fl.style.pointerEvents = ''; fl.style.opacity = '';
                });
                xhr.open('POST', 'api/upload_image.php');
                xhr.send(formData);
            }

            function clearImagePreview() {
                document.getElementById('image-preview-container').classList.add('hidden');
                document.getElementById('image-url-input').value = '';
                document.getElementById('image-url-visible').value = '';
                document.getElementById('image-file-input').value = '';
                selectedImageFile = null;
                imageUploadState = 'idle';
                document.getElementById('upload-progress').classList.add('hidden');
                updatePreview();
            }
            </script>

        <?php else: ?>
            <!-- List View -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <!-- Stats + Filters -->
                <div class="p-4 bg-gray-50 border-b">
                    <div class="flex flex-wrap items-center gap-2 mb-4">
                        <span class="px-3 py-1 text-xs rounded-full bg-gray-200 text-gray-700"><i class="fas fa-layer-group mr-1"></i><?php echo $stats['total']; ?> total</span>
                        <span class="px-3 py-1 text-xs rounded-full bg-green-100 text-green-800"><i class="fas fa-check mr-1"></i><?php echo $stats['active']; ?> active</span>
                        <span class="px-3 py-1 text-xs rounded-full bg-red-100 text-red-800"><i class="fas fa-pause mr-1"></i><?php echo $stats['inactive']; ?> inactive</span>
                    </div>
                    <form method="GET" action="testimonials.php" class="flex flex-col sm:flex-row gap-2">
                        <div class="flex-1 flex flex-col sm:flex-row gap-2">
                            <select name="filter_status" class="px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-primary focus:border-primary">
                                <option value="">All statuses</option>
                                <option value="active" <?php echo (($_GET['filter_status'] ?? '') === 'active') ? 'selected' : ''; ?>>Active only</option>
                                <option value="inactive" <?php echo (($_GET['filter_status'] ?? '') === 'inactive') ? 'selected' : ''; ?>>Inactive only</option>
                            </select>
                            <div class="relative flex-1">
                                <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                                <input type="text" name="q" value="<?php echo htmlspecialchars($_GET['q'] ?? ''); ?>"
                                       placeholder="Search name, company, or text…"
                                       class="w-full pl-9 pr-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-primary focus:border-primary">
                            </div>
                        </div>
                        <button type="submit" class="bg-primary text-white px-4 py-2 rounded-md hover:bg-secondary transition-colors text-sm inline-flex items-center justify-center">
                            <i class="fas fa-filter mr-1"></i>Filter
                        </button>
                        <?php if (!empty($_GET['filter_status']) || !empty($_GET['q'])): ?>
                        <a href="testimonials.php" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-200 transition-colors text-sm inline-flex items-center justify-center">
                            <i class="fas fa-times mr-1"></i>Clear
                        </a>
                        <?php endif; ?>
                    </form>
                </div>

                <div class="overflow-auto" style="max-height: 55vh;">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50 sticky top-0">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Client</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Testimonial</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (empty($all_testimonials)): ?>
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center">
                                    <i class="fas fa-quote-left text-4xl text-gray-300 mb-3"></i>
                                    <p class="text-gray-500 mb-2">No testimonials found.</p>
                                    <a href="?action=add" class="text-primary hover:underline text-sm"><i class="fas fa-plus mr-1"></i>Add new testimonial</a>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($all_testimonials as $t): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <?php if ($t['photo_url']): ?>
                                                <img src="<?php echo htmlspecialchars($t['photo_url']); ?>" alt="" class="w-10 h-10 rounded-full object-cover border-2 border-[#eef3f0]">
                                            <?php else: ?>
                                                <div class="w-10 h-10 rounded-full flex items-center justify-center border-2 border-[#eef3f0]" style="background: linear-gradient(135deg, #3d7a66, #60796e);">
                                                    <i class="fas fa-user text-white text-sm"></i>
                                                </div>
                                            <?php endif; ?>
                                            <div>
                                                <p class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($t['name']); ?></p>
                                                <p class="text-xs text-gray-400"><?php echo htmlspecialchars(trim(($t['position'] ?? '') . ($t['company'] ? ' · ' . $t['company'] : '')) ?: '—'); ?></p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500 max-w-md">
                                        <p class="truncate" title="<?php echo htmlspecialchars($t['testimonial']); ?>"><?php echo htmlspecialchars(mb_strimwidth($t['testimonial'], 0, 80, '…')); ?></p>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900"><?php echo (int)$t['display_order']; ?></td>
                                    <td class="px-6 py-4">
                                        <?php if ($t['is_active']): ?>
                                            <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">Active</span>
                                        <?php else: ?>
                                            <span class="px-2 py-1 text-xs rounded-full bg-red-100 text-red-800">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 text-sm font-medium whitespace-nowrap">
                                        <a href="?action=edit&id=<?php echo $t['id']; ?>" class="text-primary hover:text-secondary mr-3" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="<?php echo $view_url; ?>" target="_blank" class="text-gray-500 hover:text-gray-700 mr-3" title="View on site">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <form method="POST" action="testimonials.php" class="inline" onsubmit="return confirm('Delete this testimonial? This cannot be undone.');">
                                            <?php echo csrfTokenField(); ?>
                                            <input type="hidden" name="delete_id" value="<?php echo $t['id']; ?>">
                                            <button type="submit" class="text-red-600 hover:text-red-800" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
                </div>
                <?php
                require_once __DIR__ . '/includes/pagination.php';
                renderPagination([
                    'current_page' => $current_page_num,
                    'total_pages'   => $total_pages,
                    'total_items'   => $total_testimonials,
                    'per_page'      => $per_page,
                    'base_query'    => $_GET,
                ]);
                ?>
            </div>

            <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
                <h3 class="font-semibold text-blue-900 mb-2">Quick Links</h3>
                <ul class="text-sm text-blue-800 space-y-1">
                    <li><a href="<?php echo $view_url; ?>" target="_blank" class="hover:underline"><i class="fas fa-external-link-alt mr-1"></i>View Testimonials on Services Page</a></li>
                    <li><a href="services.php" class="hover:underline"><i class="fas fa-concierge-bell mr-1"></i>Manage Services</a></li>
                    <li><a href="index.php" class="hover:underline"><i class="fas fa-home mr-1"></i>Back to Admin Dashboard</a></li>
                </ul>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
