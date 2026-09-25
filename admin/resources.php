<?php
require_once 'config.php';
requireLogin();

// CSRF guard: all admin POSTs must carry a valid token
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !verifyCsrfToken()) {
    http_response_code(403);
    exit('Invalid security token. Reload the page and try again.');
}

$db = getDB();
$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? null;
$error = '';
$success = '';

// Known categories → public page anchor mapping
$category_anchors = [
    'Technical Data Sheets' => 'data-sheets',
    'Product Catalogs'      => 'catalogs',
    'Application Guides'    => 'guides',
];

// Status flash from PRG redirect (avoids form resubmission on refresh)
$status_param = $_GET['status'] ?? '';
if ($status_param === 'saved')   $success = 'Resource saved successfully!';
if ($status_param === 'deleted') $success = 'Resource deleted successfully!';

// Handle form submissions (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['delete_id'] ?? '') === '') {
    $title = sanitizeInput($_POST['title'] ?? '');
    $description = $_POST['description'] ?? '';
    $file_url = sanitizeInput($_POST['file_url'] ?? '');
    $file_type = sanitizeInput($_POST['file_type'] ?? '');
    $category = sanitizeInput($_POST['category'] ?? '');
    $display_order = intval($_POST['display_order'] ?? 0);
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    if (empty($title) || empty($file_url)) {
        $error = 'Title and file URL are required.';
    } elseif (empty($category)) {
        $error = 'Category is required. Please choose a category or enter a custom one.';
    } else {
        if ($action === 'add') {
            $stmt = $db->prepare("INSERT INTO resources (title, description, file_url, file_type, category, display_order, is_active, updated_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssiii", $title, $description, $file_url, $file_type, $category, $display_order, $is_active, $_SESSION['admin_id']);
            if ($stmt->execute()) {
                logActivity('create', 'resources', $db->insert_id, "Added resource: {$title}");
                redirect('resources.php?status=saved');
            } else {
                $error = 'Error: ' . $stmt->error;
            }
        } elseif ($action === 'edit' && $id) {
            $stmt = $db->prepare("UPDATE resources SET title = ?, description = ?, file_url = ?, file_type = ?, category = ?, display_order = ?, is_active = ?, updated_by = ? WHERE id = ?");
            $stmt->bind_param("sssssiiii", $title, $description, $file_url, $file_type, $category, $display_order, $is_active, $_SESSION['admin_id'], $id);
            if ($stmt->execute()) {
                logActivity('update', 'resources', $id, "Updated resource: {$title}");
                redirect('resources.php?status=saved');
            } else {
                $error = 'Error: ' . $stmt->error;
            }
        }
    }
}

// Handle delete (POST only — safer than GET)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['delete_id'] ?? '') !== '') {
    $del_id = intval($_POST['delete_id']);
    $stmt = $db->prepare("DELETE FROM resources WHERE id = ?");
    $stmt->bind_param("i", $del_id);
    if ($stmt->execute()) {
        logActivity('delete', 'resources', $del_id, 'Deleted resource');
        redirect('resources.php?status=deleted');
    } else {
        $error = 'Error deleting resource: ' . $stmt->error;
    }
}

// Get resource for edit
$edit_resource = null;
if ($action === 'edit' && $id) {
    $stmt = $db->prepare("SELECT * FROM resources WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $edit_resource = $result->fetch_assoc();
    if (!$edit_resource) {
        $error = 'Resource not found.';
        $action = 'list';
    }
}

// Get all resources for list (with pagination + filtering + search)
$all_resources = [];
$total_resources = 0;
$total_pages = 1;
$current_page_num = 1;
$per_page = 10;

if ($action === 'list') {
    $per_page = 10;
    $current_page_num = max(1, intval($_GET['page'] ?? 1));
    $filter_status = $_GET['filter_status'] ?? '';
    $filter_category = $_GET['filter_category'] ?? '';
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
    if ($filter_category !== '') {
        $where[] = 'category = ?';
        $params[] = $filter_category;
        $types .= 's';
    }
    if ($search_q !== '') {
        $where[] = '(title LIKE ? OR description LIKE ?)';
        $like = '%' . $search_q . '%';
        $params[] = $like;
        $params[] = $like;
        $types .= 'ss';
    }
    $where_sql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

    // Total count
    $count_sql = "SELECT COUNT(*) as total FROM resources $where_sql";
    $count_stmt = $db->prepare($count_sql);
    if ($types && $count_stmt) {
        $count_stmt->bind_param($types, ...$params);
    }
    if ($count_stmt) {
        $count_stmt->execute();
        $total_resources = $count_stmt->get_result()->fetch_assoc()['total'];
    }
    $total_pages = max(1, ceil($total_resources / $per_page));
    if ($current_page_num > $total_pages) {
        $current_page_num = $total_pages;
    }
    $offset = ($current_page_num - 1) * $per_page;

    // List query
    $list_sql = "SELECT * FROM resources $where_sql ORDER BY category, display_order, title LIMIT ? OFFSET ?";
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
            $all_resources[] = $row;
        }
    }
}

// Quick stats for the list header
$stats = ['total' => 0, 'active' => 0, 'inactive' => 0];
$st = $db->query("SELECT COUNT(*) total, SUM(is_active) active FROM resources");
if ($st) {
    $row = $st->fetch_assoc();
    $stats['total'] = (int)$row['total'];
    $stats['active'] = (int)$row['active'];
    $stats['inactive'] = $stats['total'] - $stats['active'];
}

// Existing categories from DB (for the filter dropdown + datalist)
$db_categories = [];
$cat_result = $db->query("SELECT DISTINCT category FROM resources WHERE category != '' ORDER BY category");
if ($cat_result) {
    while ($row = $cat_result->fetch_assoc()) {
        $db_categories[] = $row['category'];
    }
}

// Helper: build the "View on site" URL for a given category
function resourceViewUrl($category) {
    global $category_anchors;
    $anchor = $category_anchors[$category] ?? 'other-resources';
    return '../resources.php#' . $anchor;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/png" href="../uploads/images/favicon.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resources Management - <?php echo SITE_NAME; ?></title>
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
        .quick-pick { padding: 3px 8px; font-size: 11px; border: 1px solid #d1d5db; background: #fff; border-radius: 4px; cursor: pointer; color: #4b5563; }
        .quick-pick:hover { background: #f3f4f6; border-color: #9ca3af; }
    </style>

    <!-- Main Content -->
    <div class="relative lg:ml-64 p-4 lg:p-8">
        <?php $logo_pulse_logo = '../uploads/images/tupi_supreme_logo.png'; $logo_pulse_mode = 'absolute'; include '../includes/logo_pulse_loader.php'; ?>

        <!-- Page Header -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3">
                <div>
                    <h1 class="text-3xl font-bold text-gray-800 flex items-center gap-3">
                        <i class="fas fa-file-alt text-primary"></i> Resources Management
                    </h1>
                    <p class="text-sm text-gray-500 mt-1">Manage downloadable data sheets, catalogs, and application guides shown on the public Resources page.</p>
                </div>
                <?php if ($action === 'list'): ?>
                <a href="?action=add" class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-secondary transition-colors inline-flex items-center justify-center">
                    <i class="fas fa-plus mr-2"></i>Add New Resource
                </a>
                <?php else: ?>
                <a href="resources.php" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200 transition-colors inline-flex items-center justify-center">
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
            <div class="p-6">
                <h2 class="text-2xl font-bold mb-1"><?php echo $action === 'add' ? 'Add New' : 'Edit'; ?> Resource</h2>
                <p class="text-sm text-gray-500 mb-4">Fields marked <span class="text-red-500">*</span> are required.</p>

                <!-- Info banner -->
                <div class="mb-6 p-3 rounded-lg bg-blue-50 border border-blue-200 flex items-start gap-2">
                    <i class="fas fa-info-circle text-blue-500 mt-0.5"></i>
                    <p class="text-sm text-blue-800">Resources appear as download cards on the public <a href="../resources.php#data-sheets" target="_blank" class="underline hover:text-blue-900">Resources page</a>, grouped by category. Each card shows a file icon, the title, file type badge, description, and a download button. The three built-in categories (Technical Data Sheets, Product Catalogs, Application Guides) have their own sections; custom categories appear under "Other Resources".</p>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Left: form fields -->
                    <div>
                        <form method="POST" action="" onsubmit="return validateFileUpload()">
                            <?php echo csrfTokenField(); ?>
                            <div class="space-y-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Title <span class="text-red-500">*</span></label>
                                    <input type="text" name="title" id="title_input" required
                                           value="<?php echo htmlspecialchars($edit_resource['title'] ?? ''); ?>"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary"
                                           placeholder="e.g., Granulated Activated Carbon — Technical Data Sheet"
                                           oninput="updatePreview()">
                                    <p class="text-xs text-gray-400 mt-1">The name shown on the download card.</p>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Category <span class="text-red-500">*</span></label>
                                        <?php
                                        $current_category = $edit_resource['category'] ?? '';
                                        $is_known_category = isset($category_anchors[$current_category]);
                                        if (!$edit_resource && $current_category === '' && !empty($category_anchors)) {
                                            $current_category = array_key_first($category_anchors);
                                            $is_known_category = true;
                                        }
                                        ?>
                                        <input type="hidden" name="category" id="category-value" value="<?php echo htmlspecialchars($current_category); ?>">
                                        <select id="category-select" onchange="onCategoryChange(); updatePreview()" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary">
                                            <?php foreach ($category_anchors as $cat => $anchor): ?>
                                                <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo ($is_known_category && $current_category === $cat) ? 'selected' : ''; ?>><?php echo htmlspecialchars($cat); ?></option>
                                            <?php endforeach; ?>
                                            <option value="__other__" <?php echo (!$is_known_category && $current_category !== '') ? 'selected' : ''; ?>>Other…</option>
                                        </select>
                                        <input type="text" id="category-custom" value="<?php echo !$is_known_category ? htmlspecialchars($current_category) : ''; ?>" placeholder="Enter custom category name" oninput="syncCategoryCustom(this.value); updatePreview()" class="w-full mt-2 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary <?php echo ($is_known_category || $current_category === '') ? 'hidden' : ''; ?>">
                                        <p class="text-xs text-gray-400 mt-1">Built-in categories get their own section. "Other…" lets you create a custom category.</p>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">File Type</label>
                                        <input type="text" name="file_type" id="file_type_input"
                                               value="<?php echo htmlspecialchars($edit_resource['file_type'] ?? ''); ?>"
                                               placeholder="e.g., PDF"
                                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary"
                                               oninput="updatePreview()">
                                        <p class="text-xs text-gray-400 mt-1">Shown as a badge on the card.</p>
                                        <div class="mt-2 flex flex-wrap gap-1">
                                            <button type="button" class="quick-pick" onclick="setFileType('PDF')">PDF</button>
                                            <button type="button" class="quick-pick" onclick="setFileType('DOCX')">DOCX</button>
                                            <button type="button" class="quick-pick" onclick="setFileType('DOC')">DOC</button>
                                            <button type="button" class="quick-pick" onclick="setFileType('XLSX')">XLSX</button>
                                            <button type="button" class="quick-pick" onclick="setFileType('PPT')">PPT</button>
                                            <button type="button" class="quick-pick" onclick="setFileType('ZIP')">ZIP</button>
                                        </div>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">File <span class="text-red-500">*</span></label>

                                    <!-- File source mode selector -->
                                    <div class="mb-3">
                                        <label class="block text-xs font-medium text-gray-500 mb-1">File source</label>
                                        <select id="file-source-mode" onchange="switchFileMode()" class="w-full max-w-xs px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary text-sm">
                                            <option value="upload">Upload from file</option>
                                            <option value="url">Enter file URL</option>
                                        </select>
                                    </div>

                                    <!-- File Picker (upload mode) -->
                                    <div id="file-picker-block" class="border-2 border-dashed border-gray-300 rounded-lg p-4 mb-3">
                                        <div class="text-center">
                                            <input type="file" id="document-file-input" accept="application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,.pdf,.doc,.docx" class="hidden">
                                            <label for="document-file-input" class="cursor-pointer inline-flex items-center px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg transition-colors">
                                                <i class="fas fa-upload mr-2"></i>Choose Document File
                                            </label>
                                            <p class="mt-2 text-xs text-gray-500">PDF, DOC, or DOCX (Max 10MB) — uploads automatically when selected</p>
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
                                        <input type="url" id="file-url-visible" value="<?php echo htmlspecialchars($edit_resource['file_url'] ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary" placeholder="https://example.com/datasheet.pdf" oninput="syncUrlInput(this.value)">
                                        <p class="mt-1 text-xs text-gray-500">Direct link to the downloadable file. Use <code>uploads/documents/…</code> for files stored on this site.</p>
                                    </div>

                                    <!-- Hidden field: the actual value submitted with the form -->
                                    <input type="hidden" name="file_url" id="file-url-input" value="<?php echo htmlspecialchars($edit_resource['file_url'] ?? ''); ?>">
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Display Order</label>
                                        <input type="number" name="display_order" min="0"
                                               value="<?php echo htmlspecialchars($edit_resource['display_order'] ?? 0); ?>"
                                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary">
                                        <p class="text-xs text-gray-400 mt-1">Lower numbers appear first within the category. Use 10, 20, 30…</p>
                                    </div>
                                    <div class="flex items-center">
                                        <label class="flex items-center cursor-pointer select-none">
                                            <input type="checkbox" name="is_active" value="1"
                                                   <?php echo ($edit_resource && $edit_resource['is_active']) || !$edit_resource ? 'checked' : ''; ?>
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

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Description <span class="text-gray-400 font-normal">(optional)</span></label>
                                    <textarea name="description" id="description_input" rows="4"
                                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary"
                                              placeholder="e.g., Complete technical specifications including mesh size, iodine number, and ash content."
                                              oninput="updatePreview()"><?php echo htmlspecialchars($edit_resource['description'] ?? ''); ?></textarea>
                                    <p class="text-xs text-gray-400 mt-1">Short description shown under the title on the download card.</p>
                                </div>
                            </div>

                            <div class="mt-8 flex flex-col sm:flex-row gap-3">
                                <button type="submit" class="bg-primary text-white px-6 py-2.5 rounded-lg hover:bg-secondary transition-colors inline-flex items-center justify-center">
                                    <i class="fas fa-save mr-2"></i><?php echo $action === 'add' ? 'Add Resource' : 'Save Changes'; ?>
                                </button>
                                <a href="resources.php" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-6 py-2.5 rounded-lg transition-colors inline-flex items-center justify-center">
                                    <i class="fas fa-times mr-2"></i>Cancel
                                </a>
                            </div>
                        </form>
                    </div>

                    <!-- Right: live preview -->
                    <div>
                        <p class="text-sm font-medium text-gray-700 mb-2 flex items-center gap-2">
                            <i class="fas fa-eye text-gray-400"></i> Live Preview
                            <span class="text-xs text-gray-400 font-normal">(download card on the Resources page)</span>
                        </p>
                        <div class="resource-card bg-white border border-[#e6ece8] rounded-2xl p-8">
                            <div class="text-center mb-6">
                                <div class="download-icon w-16 h-16 rounded-2xl flex items-center justify-center mx-auto" style="background: #3d7a66;">
                                    <i id="preview_icon" class="fas fa-file-pdf text-2xl text-white"></i>
                                </div>
                            </div>
                            <h3 id="preview_title" class="text-xl font-semibold text-[#23332c] mb-3 text-center"><?php echo htmlspecialchars($edit_resource['title'] ?? 'Resource title'); ?></h3>
                            <p id="preview_file_type" class="text-[#8a978f] mb-3 text-center text-sm <?php echo empty($edit_resource['file_type'] ?? '') ? 'hidden' : ''; ?>"><?php echo htmlspecialchars($edit_resource['file_type'] ?? ''); ?></p>
                            <p id="preview_desc" class="text-[#7d8b84] mb-6 text-sm leading-relaxed <?php echo empty($edit_resource['description'] ?? '') ? 'hidden' : ''; ?>"><?php echo htmlspecialchars($edit_resource['description'] ?? ''); ?></p>
                            <a id="preview_download" href="#" class="block w-full bg-[#23332c] hover:bg-[#3a4a41] text-white font-medium py-3 px-6 rounded-full transition-colors text-center inline-flex items-center justify-center gap-2">
                                <i class="fas fa-download text-xs"></i><span id="preview_btn_text">Download</span>
                            </a>
                        </div>
                        <p class="text-xs text-gray-400 mt-2">The icon changes based on file type (PDF, DOC, etc.). The actual card on the public site may vary slightly.</p>
                    </div>
                </div>
            </div>

            <script>
            // Quick-pick helpers
            function setFileType(ft) {
                document.getElementById('file_type_input').value = ft;
                updatePreview();
            }

            // Live preview
            function updatePreview() {
                var title = document.getElementById('title_input').value || 'Resource title';
                var desc = document.getElementById('description_input').value || '';
                var fileType = document.getElementById('file_type_input').value || '';
                var catSelect = document.getElementById('category-select');
                var category = catSelect.value === '__other__' ? (document.getElementById('category-custom').value || 'Other') : catSelect.value;

                document.getElementById('preview_title').textContent = title;

                // File type
                var ftEl = document.getElementById('preview_file_type');
                if (fileType.trim()) {
                    ftEl.textContent = fileType;
                    ftEl.classList.remove('hidden');
                } else {
                    ftEl.classList.add('hidden');
                }

                // Description
                var descEl = document.getElementById('preview_desc');
                if (desc.trim()) {
                    descEl.textContent = desc;
                    descEl.classList.remove('hidden');
                } else {
                    descEl.classList.add('hidden');
                }

                // Icon based on file type
                var icon = 'fas fa-file-alt';
                var ftUpper = fileType.toUpperCase();
                if (ftUpper === 'PDF') icon = 'fas fa-file-pdf';
                else if (ftUpper === 'DOC' || ftUpper === 'DOCX') icon = 'fas fa-file-word';
                else if (ftUpper === 'XLS' || ftUpper === 'XLSX') icon = 'fas fa-file-excel';
                else if (ftUpper === 'PPT' || ftUpper === 'PPTX') icon = 'fas fa-file-powerpoint';
                else if (ftUpper === 'ZIP') icon = 'fas fa-file-archive';
                document.getElementById('preview_icon').className = icon + ' text-2xl text-white';

                // Button text
                document.getElementById('preview_btn_text').textContent = 'Download ' + (fileType || 'File');
            }

            // Category select logic
            function onCategoryChange() {
                const select = document.getElementById('category-select');
                const custom = document.getElementById('category-custom');
                const hidden = document.getElementById('category-value');
                if (select.value === '__other__') {
                    custom.classList.remove('hidden');
                    hidden.value = custom.value.trim();
                    custom.focus();
                } else {
                    custom.classList.add('hidden');
                    hidden.value = select.value;
                }
            }
            function syncCategoryCustom(value) {
                document.getElementById('category-value').value = value;
            }
            </script>

            <script>
            // File upload logic
            let selectedDocFile = null;
            let uploadState = 'idle';
            let fileMode = 'upload';

            function switchFileMode() {
                const mode = document.getElementById('file-source-mode').value;
                fileMode = mode;
                const fileBlock = document.getElementById('file-picker-block');
                const urlBlock = document.getElementById('url-input-block');
                if (mode === 'url') {
                    fileBlock.classList.add('hidden');
                    urlBlock.classList.remove('hidden');
                    document.getElementById('file-url-visible').value = document.getElementById('file-url-input').value;
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
                document.getElementById('file-url-input').value = value;
            }

            document.getElementById('document-file-input').addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    selectedDocFile = file;
                    uploadState = 'idle';
                    uploadDocument();
                }
            });

            function uploadDocument() {
                if (!selectedDocFile) { alert('Please select a document file first'); return; }
                const formData = new FormData();
                formData.append('document', selectedDocFile);
                const progressContainer = document.getElementById('upload-progress');
                const progressBar = document.getElementById('upload-progress-bar');
                const statusText = document.getElementById('upload-status');
                const fileLabel = document.querySelector('label[for="document-file-input"]');
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
                            document.getElementById('file-url-input').value = response.url;
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
                xhr.open('POST', 'api/upload_document.php');
                xhr.send(formData);
            }

            function validateFileUpload() {
                const catSelect = document.getElementById('category-select');
                const catCustom = document.getElementById('category-custom');
                const catHidden = document.getElementById('category-value');
                if (catSelect && catSelect.value === '__other__') {
                    if (!catCustom.value.trim()) {
                        alert('Please enter a custom category name, or pick a known category.');
                        catCustom.focus();
                        return false;
                    }
                    catHidden.value = catCustom.value.trim();
                } else if (catHidden && !catHidden.value.trim()) {
                    alert('Please choose a category.');
                    return false;
                }
                const urlInput = document.getElementById('file-url-input');
                if (fileMode === 'url') {
                    if (!urlInput.value.trim()) { alert('Please enter a file URL.'); return false; }
                    return true;
                }
                if (uploadState === 'uploading') { alert('Please wait for the file upload to finish before saving.'); return false; }
                if (selectedDocFile && uploadState === 'failed') { alert('The file upload failed. Please try again or pick a different file.'); return false; }
                if (!urlInput.value.trim()) { alert('Please choose and upload a document file first.'); return false; }
                return true;
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
                    <form method="GET" action="resources.php" class="flex flex-col sm:flex-row gap-2">
                        <div class="flex-1 flex flex-col sm:flex-row gap-2">
                            <select name="filter_status" class="px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-primary focus:border-primary">
                                <option value="">All statuses</option>
                                <option value="active" <?php echo (($_GET['filter_status'] ?? '') === 'active') ? 'selected' : ''; ?>>Active only</option>
                                <option value="inactive" <?php echo (($_GET['filter_status'] ?? '') === 'inactive') ? 'selected' : ''; ?>>Inactive only</option>
                            </select>
                            <select name="filter_category" class="px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-primary focus:border-primary">
                                <option value="">All categories</option>
                                <?php
                                $filter_cats = array_unique(array_merge(array_keys($category_anchors), $db_categories));
                                foreach ($filter_cats as $cat):
                                ?>
                                    <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo (($_GET['filter_category'] ?? '') === $cat) ? 'selected' : ''; ?>><?php echo htmlspecialchars($cat); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="relative flex-1">
                                <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                                <input type="text" name="q" value="<?php echo htmlspecialchars($_GET['q'] ?? ''); ?>"
                                       placeholder="Search title or description…"
                                       class="w-full pl-9 pr-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-primary focus:border-primary">
                            </div>
                        </div>
                        <button type="submit" class="bg-primary text-white px-4 py-2 rounded-md hover:bg-secondary transition-colors text-sm inline-flex items-center justify-center">
                            <i class="fas fa-filter mr-1"></i>Filter
                        </button>
                        <?php if (!empty($_GET['filter_status']) || !empty($_GET['filter_category']) || !empty($_GET['q'])): ?>
                        <a href="resources.php" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-200 transition-colors text-sm inline-flex items-center justify-center">
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
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Downloads</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (empty($all_resources)): ?>
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center">
                                    <i class="fas fa-file-alt text-4xl text-gray-300 mb-3"></i>
                                    <p class="text-gray-500 mb-2">No resources found.</p>
                                    <a href="?action=add" class="text-primary hover:underline text-sm"><i class="fas fa-plus mr-1"></i>Add new resource</a>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($all_resources as $resource): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 text-sm font-medium text-gray-900"><?php echo htmlspecialchars($resource['title']); ?></td>
                                    <td class="px-6 py-4">
                                        <?php if ($resource['category']): ?>
                                            <span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800"><?php echo htmlspecialchars($resource['category']); ?></span>
                                        <?php else: ?>
                                            <span class="text-gray-400 text-sm">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500"><?php echo htmlspecialchars($resource['file_type'] ?: '—'); ?></td>
                                    <td class="px-6 py-4 text-sm text-gray-900"><?php echo (int)$resource['download_count']; ?></td>
                                    <td class="px-6 py-4 text-sm text-gray-900"><?php echo (int)$resource['display_order']; ?></td>
                                    <td class="px-6 py-4">
                                        <?php if ($resource['is_active']): ?>
                                            <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">Active</span>
                                        <?php else: ?>
                                            <span class="px-2 py-1 text-xs rounded-full bg-red-100 text-red-800">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 text-sm font-medium whitespace-nowrap">
                                        <a href="?action=edit&id=<?php echo $resource['id']; ?>" class="text-primary hover:text-secondary mr-3" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="<?php echo resourceViewUrl($resource['category']); ?>" target="_blank" class="text-gray-500 hover:text-gray-700 mr-3" title="View on site">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <form method="POST" action="resources.php" class="inline" onsubmit="return confirm('Delete this resource? This cannot be undone.');">
                                            <?php echo csrfTokenField(); ?>
                                            <input type="hidden" name="delete_id" value="<?php echo $resource['id']; ?>">
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
                    'total_items'   => $total_resources,
                    'per_page'      => $per_page,
                    'base_query'    => $_GET,
                ]);
                ?>
            </div>

            <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
                <h3 class="font-semibold text-blue-900 mb-2">Quick Links</h3>
                <ul class="text-sm text-blue-800 space-y-1">
                    <li><a href="../resources.php#data-sheets" target="_blank" class="hover:underline"><i class="fas fa-external-link-alt mr-1"></i>View Resources — Data Sheets</a></li>
                    <li><a href="../resources.php#catalogs" target="_blank" class="hover:underline"><i class="fas fa-external-link-alt mr-1"></i>View Resources — Catalogs</a></li>
                    <li><a href="../resources.php#guides" target="_blank" class="hover:underline"><i class="fas fa-external-link-alt mr-1"></i>View Resources — Guides</a></li>
                    <li><a href="index.php" class="hover:underline"><i class="fas fa-home mr-1"></i>Back to Admin Dashboard</a></li>
                </ul>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
