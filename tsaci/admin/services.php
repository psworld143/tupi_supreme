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
if ($status_param === 'saved')   $success = 'Service saved successfully!';
if ($status_param === 'deleted') $success = 'Service deleted successfully!';

// Handle form submissions (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['delete_id'] ?? '') === '') {
    $title = sanitizeInput($_POST['title'] ?? '');
    $slug = generateSlug($title);
    $description = $_POST['description'] ?? '';
    $features = $_POST['features'] ?? '';
    $benefits = $_POST['benefits'] ?? '';
    $icon = sanitizeInput($_POST['icon'] ?? '');
    $image_url = sanitizeInput($_POST['image_url'] ?? '');
    $display_order = intval($_POST['display_order'] ?? 0);
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    if (empty($title)) {
        $error = 'Service title is required.';
    } else {
        if ($action === 'add') {
            $stmt = $db->prepare("INSERT INTO services (title, slug, description, features, benefits, icon, image_url, display_order, is_active, updated_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssssiii", $title, $slug, $description, $features, $benefits, $icon, $image_url, $display_order, $is_active, $_SESSION['admin_id']);
            if ($stmt->execute()) {
                logActivity('create', 'services', $db->insert_id, "Created service: {$title}");
                redirect('services.php?status=saved');
            } else {
                $error = 'Error: ' . $stmt->error;
            }
        } elseif ($action === 'edit' && $id) {
            $stmt = $db->prepare("UPDATE services SET title = ?, slug = ?, description = ?, features = ?, benefits = ?, icon = ?, image_url = ?, display_order = ?, is_active = ?, updated_by = ? WHERE id = ?");
            $stmt->bind_param("sssssssiiii", $title, $slug, $description, $features, $benefits, $icon, $image_url, $display_order, $is_active, $_SESSION['admin_id'], $id);
            if ($stmt->execute()) {
                logActivity('update', 'services', $id, "Updated service: {$title}");
                redirect('services.php?status=saved');
            } else {
                $error = 'Error: ' . $stmt->error;
            }
        }
    }
}

// Handle delete (POST only — safer than GET)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['delete_id'] ?? '') !== '') {
    $del_id = intval($_POST['delete_id']);
    $stmt = $db->prepare("DELETE FROM services WHERE id = ?");
    $stmt->bind_param("i", $del_id);
    if ($stmt->execute()) {
        logActivity('delete', 'services', $del_id, 'Deleted service');
        redirect('services.php?status=deleted');
    } else {
        $error = 'Error deleting service: ' . $stmt->error;
    }
}

// Get service for edit
$edit_service = null;
if ($action === 'edit' && $id) {
    $stmt = $db->prepare("SELECT * FROM services WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $edit_service = $result->fetch_assoc();
    if (!$edit_service) {
        $error = 'Service not found.';
        $action = 'list';
    }
}

// Get all services for list (with pagination + filtering + search)
$all_services = [];
$total_services = 0;
$total_pages = 1;
$current_page_num = 1;
$per_page = 10;

if ($action === 'list') {
    $per_page = 10;
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
        $where[] = '(title LIKE ? OR description LIKE ? OR features LIKE ?)';
        $like = '%' . $search_q . '%';
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
        $types .= 'sss';
    }
    $where_sql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

    // Total count
    $count_sql = "SELECT COUNT(*) as total FROM services $where_sql";
    $count_stmt = $db->prepare($count_sql);
    if ($types && $count_stmt) {
        $count_stmt->bind_param($types, ...$params);
    }
    if ($count_stmt) {
        $count_stmt->execute();
        $total_services = $count_stmt->get_result()->fetch_assoc()['total'];
    }
    $total_pages = max(1, ceil($total_services / $per_page));
    if ($current_page_num > $total_pages) {
        $current_page_num = $total_pages;
    }
    $offset = ($current_page_num - 1) * $per_page;

    // List query
    $list_sql = "SELECT * FROM services $where_sql ORDER BY display_order, title LIMIT ? OFFSET ?";
    $list_stmt = $db->prepare($list_sql);
    $list_params = $params;
    $list_types = $types . 'ii';
    $list_params[] = $per_page;
    $list_params[] = $offset;
    if ($list_stmt) {
        $list_stmt->bind_param($list_types, ...$list_params);
        $list_stmt->execute();
        $result = $list_stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $all_services[] = $row;
        }
    }
}

// Quick stats for the list header
$stats = ['total' => 0, 'active' => 0, 'inactive' => 0];
$st = $db->query("SELECT COUNT(*) total, SUM(is_active) active FROM services");
if ($st) {
    $row = $st->fetch_assoc();
    $stats['total'] = (int)$row['total'];
    $stats['active'] = (int)$row['active'];
    $stats['inactive'] = $stats['total'] - $stats['active'];
}

// All services render in the main services section on the public page
$view_url = '../services.php#main';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Services Management - <?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <?php if ($action === 'add' || $action === 'edit'): ?>
    <!-- Lightweight inline HTML editor (replaces EOL CKEditor 4) -->
    <style>
        .qe-toolbar button { padding: 4px 8px; border: 1px solid #d1d5db; background: #fff; border-radius: 4px; font-size: 13px; cursor: pointer; }
        .qe-toolbar button:hover { background: #f3f4f6; }
        .qe-editor { min-height: 120px; }
        .qe-editor:focus { outline: none; border-color: #2c5530; }
        .qe-editor:empty:before { content: attr(data-placeholder); color: #9ca3af; }
        .quick-pick { padding: 3px 8px; font-size: 11px; border: 1px solid #d1d5db; background: #fff; border-radius: 4px; cursor: pointer; color: #4b5563; }
        .quick-pick:hover { background: #f3f4f6; border-color: #9ca3af; }
    </style>
    <?php endif; ?>
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
                        <i class="fas fa-concierge-bell text-primary"></i> Services Management
                    </h1>
                    <p class="text-sm text-gray-500 mt-1">Manage the service offerings shown on the public Services page.</p>
                </div>
                <?php if ($action === 'list'): ?>
                <a href="?action=add" class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-secondary transition-colors inline-flex items-center justify-center">
                    <i class="fas fa-plus mr-2"></i>Add New Service
                </a>
                <?php else: ?>
                <a href="services.php" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200 transition-colors inline-flex items-center justify-center">
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
                <h2 class="text-2xl font-bold mb-1"><?php echo $action === 'add' ? 'Add New' : 'Edit'; ?> Service</h2>
                <p class="text-sm text-gray-500 mb-4">Fields marked <span class="text-red-500">*</span> are required.</p>

                <!-- Info banner -->
                <div class="mb-6 p-3 rounded-lg bg-blue-50 border border-blue-200 flex items-start gap-2">
                    <i class="fas fa-info-circle text-blue-500 mt-0.5"></i>
                    <p class="text-sm text-blue-800">Services appear as cards in a 3-column grid on the public <a href="<?php echo $view_url; ?>" target="_blank" class="underline hover:text-blue-900">Services page</a>. The icon shows in a green gradient box at the top of each card. <strong>Features</strong> are split on newlines — one feature per line, shown as a checklist.</p>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Left: form fields -->
                    <div>
                        <form method="POST" action="">
                            <div class="space-y-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Title <span class="text-red-500">*</span></label>
                                    <input type="text" name="title" id="title_input" required
                                           value="<?php echo htmlspecialchars($edit_service['title'] ?? ''); ?>"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary"
                                           placeholder="e.g., Technical Consultation"
                                           oninput="updatePreview()">
                                    <p class="text-xs text-gray-400 mt-1">A slug is auto-generated from the title (e.g., "Technical Consultation" → "technical-consultation").</p>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Icon <span class="text-gray-400 font-normal">(optional)</span></label>
                                        <div class="flex items-center gap-3">
                                            <input type="text" name="icon" id="icon_input"
                                                   value="<?php echo htmlspecialchars($edit_service['icon'] ?? ''); ?>"
                                                   placeholder="e.g., fas fa-check"
                                                   class="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary font-mono"
                                                   oninput="updateIconPreview(this.value); updatePreview()">
                                            <div id="icon_preview" class="flex items-center justify-center w-12 h-12 rounded-xl text-white" style="background: linear-gradient(135deg, #3d7a66, #60796e);">
                                                <i class="<?php echo htmlspecialchars($edit_service['icon'] ?? 'fas fa-check'); ?> text-2xl"></i>
                                            </div>
                                        </div>
                                        <p class="text-xs text-gray-400 mt-1">Font Awesome icon class (with <code>fas</code> prefix). <a href="https://fontawesome.com/v6/search?o=r&m=free" target="_blank" class="text-primary hover:underline">Browse icons</a>.</p>
                                        <div class="mt-2 flex flex-wrap gap-1">
                                            <button type="button" class="quick-pick" onclick="setIcon('fas fa-check')">fa-check</button>
                                            <button type="button" class="quick-pick" onclick="setIcon('fas fa-cogs')">fa-cogs</button>
                                            <button type="button" class="quick-pick" onclick="setIcon('fas fa-flask')">fa-flask</button>
                                            <button type="button" class="quick-pick" onclick="setIcon('fas fa-clipboard-check')">fa-clipboard-check</button>
                                            <button type="button" class="quick-pick" onclick="setIcon('fas fa-shipping-fast')">fa-shipping-fast</button>
                                            <button type="button" class="quick-pick" onclick="setIcon('fas fa-headset')">fa-headset</button>
                                            <button type="button" class="quick-pick" onclick="setIcon('fas fa-warehouse')">fa-warehouse</button>
                                            <button type="button" class="quick-pick" onclick="setIcon('fas fa-truck')">fa-truck</button>
                                            <button type="button" class="quick-pick" onclick="setIcon('fas fa-leaf')">fa-leaf</button>
                                        </div>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Display Order</label>
                                        <input type="number" name="display_order" min="0"
                                               value="<?php echo htmlspecialchars($edit_service['display_order'] ?? 0); ?>"
                                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary">
                                        <p class="text-xs text-gray-400 mt-1">Lower numbers appear first. Use 10, 20, 30…</p>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Image <span class="text-gray-400 font-normal">(optional)</span></label>

                                    <!-- Image source mode selector -->
                                    <div class="mb-3">
                                        <label class="block text-xs font-medium text-gray-500 mb-1">Image source</label>
                                        <select id="image-source-mode" onchange="switchImageMode()" class="w-full max-w-xs px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary text-sm">
                                            <option value="upload">Upload from file</option>
                                            <option value="url">Enter image URL</option>
                                        </select>
                                    </div>

                                    <!-- Image Preview -->
                                    <div id="image-preview-container" class="mb-3 <?php echo empty($edit_service['image_url'] ?? '') ? 'hidden' : ''; ?>">
                                        <img id="image-preview" src="<?php echo htmlspecialchars($edit_service['image_url'] ?? ''); ?>" alt="Preview" class="max-w-full h-48 object-contain border border-gray-300 rounded-lg p-2 bg-gray-50">
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
                                            <p class="mt-2 text-xs text-gray-500">JPEG, PNG, GIF, or WebP (Max 10MB) — uploads automatically when selected</p>
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
                                        <input type="url" id="image-url-visible" value="<?php echo htmlspecialchars($edit_service['image_url'] ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary" placeholder="https://example.com/image.jpg" oninput="syncUrlInput(this.value)">
                                        <p class="mt-1 text-xs text-gray-500">Paste a full image URL (https://...).</p>
                                    </div>

                                    <!-- Hidden field: the actual value submitted with the form -->
                                    <input type="hidden" name="image_url" id="image-url-input" value="<?php echo htmlspecialchars($edit_service['image_url'] ?? ''); ?>">
                                    <p class="text-xs text-gray-400 mt-1">Optional image for this service. Stored for future use in the card layout.</p>
                                </div>

                                <!-- Rich text editor fields -->
                                <?php
                                $rich_fields = [
                                    ['name' => 'description', 'label' => 'Description', 'hint' => 'Main service description shown on the card. Supports formatting (bold, lists, links).', 'rows' => 5, 'placeholder' => 'Describe the service…'],
                                    ['name' => 'features', 'label' => 'Features', 'hint' => 'What this service includes. The public site splits this on <strong>newlines</strong> — one feature per line, shown as a checklist.', 'rows' => 5, 'placeholder' => 'Free technical consultation&#10;On-site water analysis&#10;Custom product recommendations&#10;Ongoing support'],
                                    ['name' => 'benefits', 'label' => 'Benefits', 'hint' => 'Why customers should choose this service. Supports formatting.', 'rows' => 5, 'placeholder' => 'Reduce operating costs&#10;Improve water quality&#10;Meet compliance standards'],
                                ];
                                foreach ($rich_fields as $rf):
                                    $fid = $rf['name'] . '_html';
                                    $tid = $rf['name'];
                                ?>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2"><?php echo $rf['label']; ?></label>
                                    <div class="qe-toolbar flex flex-wrap gap-1 mb-2 p-2 bg-gray-50 rounded-t-md border border-b-0 border-gray-300" data-target="<?php echo $tid; ?>">
                                        <button type="button" data-cmd="bold" title="Bold"><b>B</b></button>
                                        <button type="button" data-cmd="italic" title="Italic"><i>I</i></button>
                                        <button type="button" data-cmd="underline" title="Underline"><u>U</u></button>
                                        <button type="button" data-cmd="insertUnorderedList" title="Bullet list"><i class="fas fa-list-ul"></i></button>
                                        <button type="button" data-cmd="insertOrderedList" title="Numbered list"><i class="fas fa-list-ol"></i></button>
                                        <button type="button" data-cmd="formatBlock" data-val="h4" title="Small heading">H4</button>
                                        <button type="button" data-cmd="formatBlock" data-val="p" title="Paragraph">P</button>
                                        <button type="button" data-cmd="createLink" title="Link"><i class="fas fa-link"></i></button>
                                        <button type="button" data-cmd="removeFormat" title="Clear formatting"><i class="fas fa-eraser"></i></button>
                                    </div>
                                    <div id="<?php echo $fid; ?>" contenteditable="true" data-placeholder="<?php echo htmlspecialchars($rf['placeholder']); ?>"
                                         class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary qe-editor bg-white prose prose-sm max-w-none"
                                         oninput="document.getElementById('<?php echo $tid; ?>').value = this.innerHTML; updatePreview();"><?php echo htmlspecialchars_decode($edit_service[$rf['name']] ?? '', ENT_QUOTES); ?></div>
                                    <textarea name="<?php echo $rf['name']; ?>" id="<?php echo $tid; ?>" rows="<?php echo $rf['rows']; ?>" class="hidden"><?php echo htmlspecialchars_decode($edit_service[$rf['name']] ?? '', ENT_QUOTES); ?></textarea>
                                    <p class="text-xs text-gray-400 mt-1"><?php echo $rf['hint']; ?></p>
                                </div>
                                <?php endforeach; ?>

                                <div>
                                    <label class="flex items-center cursor-pointer select-none">
                                        <input type="checkbox" name="is_active" value="1"
                                               <?php echo ($edit_service && $edit_service['is_active']) || !$edit_service ? 'checked' : ''; ?>
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

                            <div class="mt-8 flex flex-col sm:flex-row gap-3">
                                <button type="submit" class="bg-primary text-white px-6 py-2.5 rounded-lg hover:bg-secondary transition-colors inline-flex items-center justify-center">
                                    <i class="fas fa-save mr-2"></i><?php echo $action === 'add' ? 'Add Service' : 'Save Changes'; ?>
                                </button>
                                <a href="services.php" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-6 py-2.5 rounded-lg transition-colors inline-flex items-center justify-center">
                                    <i class="fas fa-times mr-2"></i>Cancel
                                </a>
                            </div>
                        </form>
                    </div>

                    <!-- Right: live preview -->
                    <div>
                        <p class="text-sm font-medium text-gray-700 mb-2 flex items-center gap-2">
                            <i class="fas fa-eye text-gray-400"></i> Live Preview
                            <span class="text-xs text-gray-400 font-normal">(service card on the Services page)</span>
                        </p>
                        <div class="service-card bg-white border border-[#e6ece8] rounded-2xl p-8 h-full">
                            <div class="text-center">
                                <div id="preview_icon_wrap" class="service-icon w-20 h-20 rounded-2xl flex items-center justify-center mx-auto mb-6" style="background: linear-gradient(135deg, #3d7a66, #60796e);">
                                    <i id="preview_icon" class="<?php echo htmlspecialchars($edit_service['icon'] ?? 'fas fa-check'); ?> text-3xl text-white"></i>
                                </div>
                                <h4 id="preview_title" class="text-xl font-semibold text-[#23332c] mb-3"><?php echo htmlspecialchars($edit_service['title'] ?? 'Service title'); ?></h4>
                                <div id="preview_desc" class="text-[#7d8b84] mb-6 leading-relaxed text-sm prose prose-sm max-w-none"><?php echo $edit_service['description'] ?? '<span class="text-gray-400">Service description appears here…</span>'; ?></div>
                                <ul id="preview_features" class="text-left space-y-2.5">
                                    <li class="text-gray-400 text-sm">Features appear here (one per line)…</li>
                                </ul>
                            </div>
                        </div>
                        <p class="text-xs text-gray-400 mt-2">The actual card on the public site may vary slightly. Features are split on newlines and shown as a checklist with green checkmarks.</p>
                    </div>
                </div>
            </div>

            <script>
            // Quick-pick helper
            function setIcon(i) {
                document.getElementById('icon_input').value = i;
                updateIconPreview(i);
                updatePreview();
            }

            // Live icon preview (in the form's icon box)
            function updateIconPreview(iconClass) {
                document.getElementById('icon_preview').innerHTML = '<i class="' + iconClass + ' text-2xl"></i>';
            }

            // Lightweight HTML editor toolbars
            (function () {
                document.querySelectorAll('.qe-toolbar[data-target]').forEach(function (toolbar) {
                    var targetId = toolbar.getAttribute('data-target');
                    var editor = document.getElementById(targetId + '_html');
                    if (!editor) return;
                    toolbar.addEventListener('mousedown', function (e) {
                        var btn = e.target.closest('button[data-cmd]');
                        if (!btn) return;
                        e.preventDefault();
                        editor.focus();
                        var cmd = btn.getAttribute('data-cmd');
                        var val = btn.getAttribute('data-val');
                        if (cmd === 'createLink') {
                            var url = prompt('Link URL:', 'https://');
                            if (url) document.execCommand('createLink', false, url);
                        } else if (val) {
                            document.execCommand(cmd, false, val);
                        } else {
                            document.execCommand(cmd, false, null);
                        }
                        document.getElementById(targetId).value = editor.innerHTML;
                        updatePreview();
                    });
                });
                // Sync editors -> hidden textareas before submit
                var form = document.querySelector('form[method="POST"]');
                if (form) {
                    form.addEventListener('submit', function () {
                        ['description', 'features', 'benefits'].forEach(function (id) {
                            var editor = document.getElementById(id + '_html');
                            var ta = document.getElementById(id);
                            if (editor && ta) ta.value = editor.innerHTML;
                        });
                    });
                }
            })();

            // Live preview
            function updatePreview() {
                var title = document.getElementById('title_input').value || 'Service title';
                var descHtml = document.getElementById('description_html').innerHTML || '<span class="text-gray-400">Service description appears here…</span>';
                var featuresHtml = document.getElementById('features_html').innerHTML || '';
                var icon = document.getElementById('icon_input').value || 'fas fa-check';

                document.getElementById('preview_title').textContent = title;
                document.getElementById('preview_desc').innerHTML = descHtml;
                document.getElementById('preview_icon').className = icon + ' text-3xl text-white';

                // Parse features: split on newlines (the public site does this)
                var feats = featuresHtml.split(/\n|<br\s*\/?>|<\/li>|<\/p>/i)
                    .map(function(s) { return s.replace(/<[^>]*>/g, '').trim(); })
                    .filter(function(s) { return s.length > 0; });
                var ul = document.getElementById('preview_features');
                if (feats.length > 0) {
                    ul.innerHTML = feats.map(function(f) {
                        return '<li class="flex items-start text-[#5a6b62] text-sm"><i class="fas fa-check text-[#3d7a66] mr-3 mt-1"></i>' + escapeHtml(f) + '</li>';
                    }).join('');
                } else {
                    ul.innerHTML = '<li class="text-gray-400 text-sm">Features appear here (one per line)…</li>';
                }
            }

            function escapeHtml(s) {
                return s.replace(/[&<>"']/g, function(c) {
                    return {'&':'&','<':'<','>':'>','"':'"',"'":'&#39;'}[c];
                });
            }
            </script>

            <script>
            // Image upload logic (upload from file vs enter URL)
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
                    <form method="GET" action="services.php" class="flex flex-col sm:flex-row gap-2">
                        <div class="flex-1 flex flex-col sm:flex-row gap-2">
                            <select name="filter_status" class="px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-primary focus:border-primary">
                                <option value="">All statuses</option>
                                <option value="active" <?php echo (($_GET['filter_status'] ?? '') === 'active') ? 'selected' : ''; ?>>Active only</option>
                                <option value="inactive" <?php echo (($_GET['filter_status'] ?? '') === 'inactive') ? 'selected' : ''; ?>>Inactive only</option>
                            </select>
                            <div class="relative flex-1">
                                <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                                <input type="text" name="q" value="<?php echo htmlspecialchars($_GET['q'] ?? ''); ?>"
                                       placeholder="Search title, description, or features…"
                                       class="w-full pl-9 pr-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-primary focus:border-primary">
                            </div>
                        </div>
                        <button type="submit" class="bg-primary text-white px-4 py-2 rounded-md hover:bg-secondary transition-colors text-sm inline-flex items-center justify-center">
                            <i class="fas fa-filter mr-1"></i>Filter
                        </button>
                        <?php if (!empty($_GET['filter_status']) || !empty($_GET['q'])): ?>
                        <a href="services.php" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-200 transition-colors text-sm inline-flex items-center justify-center">
                            <i class="fas fa-times mr-1"></i>Clear
                        </a>
                        <?php endif; ?>
                    </form>
                </div>

                <div class="overflow-auto" style="max-height: 55vh;">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50 sticky top-0">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Title</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Slug</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (empty($all_services)): ?>
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center">
                                    <i class="fas fa-concierge-bell text-4xl text-gray-300 mb-3"></i>
                                    <p class="text-gray-500 mb-2">No services found.</p>
                                    <a href="?action=add" class="text-primary hover:underline text-sm"><i class="fas fa-plus mr-1"></i>Add new service</a>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($all_services as $service): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($service['title']); ?></div>
                                        <?php if (!empty($service['icon'])): ?>
                                            <span class="text-xs text-gray-400"><i class="<?php echo htmlspecialchars($service['icon']); ?>"></i> <?php echo htmlspecialchars($service['icon']); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500 font-mono"><?php echo htmlspecialchars($service['slug']); ?></td>
                                    <td class="px-6 py-4 text-sm text-gray-900"><?php echo (int)$service['display_order']; ?></td>
                                    <td class="px-6 py-4">
                                        <?php if ($service['is_active']): ?>
                                            <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">Active</span>
                                        <?php else: ?>
                                            <span class="px-2 py-1 text-xs rounded-full bg-red-100 text-red-800">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 text-sm font-medium whitespace-nowrap">
                                        <a href="?action=edit&id=<?php echo $service['id']; ?>" class="text-primary hover:text-secondary mr-3" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="<?php echo $view_url; ?>" target="_blank" class="text-gray-500 hover:text-gray-700 mr-3" title="View on site">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <form method="POST" action="services.php" class="inline" onsubmit="return confirm('Delete this service? This cannot be undone.');">
                                            <input type="hidden" name="delete_id" value="<?php echo $service['id']; ?>">
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
                    'total_items'   => $total_services,
                    'per_page'      => $per_page,
                    'base_query'    => $_GET,
                ]);
                ?>
            </div>

            <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
                <h3 class="font-semibold text-blue-900 mb-2">Quick Links</h3>
                <ul class="text-sm text-blue-800 space-y-1">
                    <li><a href="<?php echo $view_url; ?>" target="_blank" class="hover:underline"><i class="fas fa-external-link-alt mr-1"></i>View Services Page</a></li>
                    <li><a href="index.php" class="hover:underline"><i class="fas fa-home mr-1"></i>Back to Admin Dashboard</a></li>
                </ul>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
