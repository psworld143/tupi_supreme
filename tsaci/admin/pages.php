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
if ($status_param === 'saved')   $success = 'Content saved successfully!';
if ($status_param === 'deleted') $success = 'Content deleted successfully!';

// Available pages (key => [label, public url])
$available_pages = [
    'index'          => ['Homepage',       '../index.php'],
    'about'          => ['About Us',       '../about.php'],
    'products'       => ['Products',       '../products.php'],
    'services'       => ['Services',       '../services.php'],
    'case-studies'   => ['Case Studies',   '../case-studies.php'],
    'gallery'        => ['Gallery',        '../gallery.php'],
    'resources'      => ['Resources',      '../resources.php'],
    'certifications' => ['Certifications', '../certifications.php'],
    'contact'        => ['Contact',        '../contact.php'],
];

// Maps section_name prefixes to the anchor IDs on the public pages.
// Longest matching prefix wins, so order doesn't matter.
$section_anchors = [
    'hero'            => 'hero',
    'features'        => 'features',
    'products'        => 'products',
    'cta'             => 'cta',
    'page_header'     => 'page-header',
    'section'         => 'main',
    'specifications'  => 'specifications',
    'applications'    => 'applications',
    'data_sheets'     => 'data-sheets',
    'catalogs'        => 'catalogs',
    'guides'          => 'guides',
    'faqs'            => 'faqs',
    'process'         => 'process',
    'testimonials'    => 'testimonials',
    'contact_section' => 'contact-section',
    'form'            => 'contact-form',
    'map'             => 'map',
    'office_hours'    => 'office-hours',
    'contact_info'    => 'contact-section',
];

// Derive the on-page anchor for a given section_name (longest-prefix match).
function getSectionAnchor($section_name, $anchors) {
    $best = null;
    $best_len = 0;
    foreach ($anchors as $prefix => $anchor) {
        $len = strlen($prefix);
        if (strpos($section_name, $prefix) === 0 && $len > $best_len) {
            $best = $anchor;
            $best_len = $len;
        }
    }
    return $best;
}

// Handle form submissions (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $page_name = sanitizeInput($_POST['page_name'] ?? '');
    $section_name = sanitizeInput($_POST['section_name'] ?? '');
    $content = $_POST['content'] ?? '';
    $content_type = sanitizeInput($_POST['content_type'] ?? 'text');
    $display_order = intval($_POST['display_order'] ?? 0);
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    if (empty($page_name) || empty($section_name)) {
        $error = 'Page and section name are required.';
    } else {
        if ($action === 'add') {
            $stmt = $db->prepare("INSERT INTO page_content (page_name, section_name, content_type, content, display_order, is_active, updated_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssiii", $page_name, $section_name, $content_type, $content, $display_order, $is_active, $_SESSION['admin_id']);

            if ($stmt->execute()) {
                logActivity('create', 'page_content', $db->insert_id, "Created content for {$page_name} - {$section_name}");
                redirect('pages.php?status=saved');
            } else {
                $error = 'Error adding content: ' . $stmt->error;
            }
        } elseif ($action === 'edit' && $id) {
            $stmt = $db->prepare("UPDATE page_content SET page_name = ?, section_name = ?, content_type = ?, content = ?, display_order = ?, is_active = ?, updated_by = ? WHERE id = ?");
            $stmt->bind_param("ssssiiii", $page_name, $section_name, $content_type, $content, $display_order, $is_active, $_SESSION['admin_id'], $id);

            if ($stmt->execute()) {
                logActivity('update', 'page_content', $id, "Updated content for {$page_name} - {$section_name}");
                redirect('pages.php?status=saved');
            } else {
                $error = 'Error updating content: ' . $stmt->error;
            }
        }
    }
}

// Handle delete (POST only — safer than GET)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['delete_id'] ?? '') !== '') {
    $del_id = intval($_POST['delete_id']);
    $stmt = $db->prepare("DELETE FROM page_content WHERE id = ?");
    $stmt->bind_param("i", $del_id);
    if ($stmt->execute()) {
        logActivity('delete', 'page_content', $del_id, 'Deleted page content');
        redirect('pages.php?status=deleted');
    } else {
        $error = 'Error deleting content: ' . $stmt->error;
    }
}

// Get content for edit
$edit_content = null;
if ($action === 'edit' && $id) {
    $stmt = $db->prepare("SELECT * FROM page_content WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $edit_content = $result->fetch_assoc();

    if (!$edit_content) {
        $error = 'Content not found.';
        $action = 'list';
    }
}

// Gather existing section names for the datalist suggestions
$section_suggestions = [];
$res = $db->query("SELECT DISTINCT section_name FROM page_content ORDER BY section_name");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $section_suggestions[] = $row['section_name'];
    }
}

// Known/available section names per page (not all may exist as rows yet).
// Surfacing these in the datalist makes it obvious which keys a page reads,
// so editors can create content for sections that aren't seeded yet.
$known_section_names = [
    'index' => [
        'hero_title', 'hero_subtitle', 'hero_badge', 'hero_button_1_text', 'hero_button_1_link',
        'hero_button_2_text', 'hero_button_2_link', 'features_title', 'features_subtitle',
        'stats_title', 'stats_subtitle', 'featured_products_title', 'featured_products_subtitle',
        'cta_title', 'cta_description', 'cta_button_1_text', 'cta_button_1_link',
        'cta_button_2_text', 'cta_button_2_link', 'meta_description',
    ],
    'about' => [
        'page_header_title', 'page_header_subtitle', 'story_title', 'story_content',
        'mission_title', 'mission_content', 'vision_title', 'vision_content', 'vision_icon',
        'timeline_title', 'timeline_subtitle', 'values_title', 'values_subtitle',
        'team_title', 'team_subtitle', 'meta_description',
    ],
    'products' => [
        'page_header_title', 'page_header_subtitle', 'section_title', 'section_subtitle',
        'tab_granulated_label', 'tab_husk_label', 'tab_custom_label',
        'specifications_title', 'specifications_subtitle', 'applications_title', 'applications_subtitle',
        'all_products_title', 'all_products_subtitle',
        'cta_title', 'cta_description', 'cta_button_1_text', 'cta_button_1_link',
        'cta_button_2_text', 'cta_button_2_link', 'meta_description',
    ],
    'services' => [
        'page_header_title', 'page_header_subtitle', 'section_title', 'section_subtitle',
        'process_title', 'process_subtitle', 'testimonials_title', 'testimonials_subtitle',
        'cta_title', 'cta_description', 'cta_button_1_text', 'cta_button_1_link',
        'cta_button_2_text', 'cta_button_2_link', 'meta_description',
    ],
    'case-studies' => [
        'page_header_title', 'page_header_subtitle', 'section_title', 'section_subtitle',
        'cta_title', 'cta_description', 'cta_button_1_text', 'cta_button_1_link',
        'cta_button_2_text', 'cta_button_2_link', 'meta_description',
    ],
    'gallery' => [
        'page_header_title', 'page_header_subtitle', 'section_title', 'section_subtitle',
        'meta_description',
    ],
    'resources' => [
        'page_header_title', 'page_header_subtitle', 'data_sheets_title', 'data_sheets_subtitle',
        'catalogs_title', 'catalogs_subtitle', 'guides_title', 'guides_subtitle',
        'other_title', 'other_subtitle', 'faqs_title', 'faqs_subtitle',
        'download_button_text', 'download_guide_text',
        'cta_title', 'cta_description', 'cta_button_1_text', 'cta_button_1_link',
        'cta_button_2_text', 'cta_button_2_link', 'meta_description',
    ],
    'certifications' => [
        'page_header_title', 'page_header_subtitle', 'section_title', 'section_subtitle',
        'meta_description',
    ],
    'contact' => [
        'page_header_title', 'page_header_subtitle', 'contact_section_title', 'contact_section_subtitle',
        'form_title', 'form_subtitle', 'map_title', 'map_url',
        'office_hours_title', 'contact_info_title', 'meta_description',
    ],
];
// Merge known names into the suggestions (deduped, sorted)
$section_suggestions = array_unique(array_merge($section_suggestions, ...array_values($known_section_names)));
sort($section_suggestions);

// Get all content for list (with pagination + filtering + search)
$all_content = [];
$total_content = 0;
$total_pages = 1;
$current_page_num = 1;
$per_page = 10;

if ($action === 'list') {
    $per_page = 10;
    $current_page_num = max(1, intval($_GET['page'] ?? 1));
    $filter_page = $_GET['filter_page'] ?? '';
    $search_q = trim($_GET['q'] ?? '');

    // Build dynamic WHERE
    $where = [];
    $params = [];
    $types = '';
    if ($filter_page !== '' && isset($available_pages[$filter_page])) {
        $where[] = 'pc.page_name = ?';
        $params[] = $filter_page;
        $types .= 's';
    }
    if ($search_q !== '') {
        $where[] = '(pc.section_name LIKE ? OR pc.content LIKE ?)';
        $like = '%' . $search_q . '%';
        $params[] = $like;
        $params[] = $like;
        $types .= 'ss';
    }
    $where_sql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

    // Total count
    $count_sql = "SELECT COUNT(*) as total FROM page_content pc $where_sql";
    $count_stmt = $db->prepare($count_sql);
    if ($types && $count_stmt) {
        $count_stmt->bind_param($types, ...$params);
    }
    if ($count_stmt) {
        $count_stmt->execute();
        $total_content = $count_stmt->get_result()->fetch_assoc()['total'];
    }
    $total_pages = max(1, ceil($total_content / $per_page));
    if ($current_page_num > $total_pages) {
        $current_page_num = $total_pages;
    }
    $offset = ($current_page_num - 1) * $per_page;

    // List query
    $list_sql = "SELECT pc.*, au.username as updated_by_name FROM page_content pc LEFT JOIN admin_users au ON pc.updated_by = au.id $where_sql ORDER BY pc.page_name, pc.display_order, pc.section_name LIMIT ? OFFSET ?";
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
            $all_content[] = $row;
        }
    }
}

// Quick stats for the list header
$stats = ['total' => 0, 'active' => 0, 'inactive' => 0];
$st = $db->query("SELECT COUNT(*) total, SUM(is_active) active FROM page_content");
if ($st) {
    $row = $st->fetch_assoc();
    $stats['total'] = (int)$row['total'];
    $stats['active'] = (int)$row['active'];
    $stats['inactive'] = $stats['total'] - $stats['active'];
}

// Content type metadata (label + badge color)
$content_types = [
    'text'      => ['Text',       'bg-blue-100 text-blue-800'],
    'html'      => ['HTML',       'bg-purple-100 text-purple-800'],
    'json'      => ['JSON',       'bg-amber-100 text-amber-800'],
    'image_url' => ['Image URL',  'bg-emerald-100 text-emerald-800'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page Content Management - <?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <?php if ($action === 'add' || $action === 'edit'): ?>
    <script src="https://cdn.ckeditor.com/4.16.2/standard/ckeditor.js"></script>
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
                        <i class="fas fa-file-alt text-primary"></i> Page Content Management
                    </h1>
                    <p class="text-sm text-gray-500 mt-1">Edit the text, images, and sections shown across the public website.</p>
                </div>
                <?php if ($action === 'list'): ?>
                <a href="?action=add" class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-secondary transition-colors inline-flex items-center justify-center">
                    <i class="fas fa-plus mr-2"></i>Add New Content
                </a>
                <?php else: ?>
                <a href="pages.php" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200 transition-colors inline-flex items-center justify-center">
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
                <h2 class="text-2xl font-bold mb-1"><?php echo $action === 'add' ? 'Add New' : 'Edit'; ?> Page Content</h2>
                <p class="text-sm text-gray-500 mb-6">Fields marked <span class="text-red-500">*</span> are required.</p>

                <form method="POST" action="">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Page <span class="text-red-500">*</span></label>
                            <select name="page_name" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary">
                                <option value="">Select a page…</option>
                                <?php foreach ($available_pages as $key => $info): ?>
                                    <option value="<?php echo $key; ?>" <?php echo ($edit_content && $edit_content['page_name'] === $key) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($info[0]); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="text-xs text-gray-400 mt-1">Which public page this content belongs to.</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Section Name <span class="text-red-500">*</span></label>
                            <input type="text" name="section_name" required list="section_suggestions"
                                   id="section_name_input"
                                   value="<?php echo htmlspecialchars($edit_content['section_name'] ?? ''); ?>"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary"
                                   placeholder="e.g., hero_title, about_description">
                            <datalist id="section_suggestions">
                                <?php foreach ($section_suggestions as $s): ?>
                                    <option value="<?php echo htmlspecialchars($s); ?>">
                                <?php endforeach; ?>
                            </datalist>
                            <p class="text-xs text-gray-400 mt-1">A unique label identifying this section (snake_case recommended). Suggestions update based on the selected page.</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Content Type</label>
                            <select name="content_type" id="content_type" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary">
                                <option value="text"      <?php echo ($edit_content && $edit_content['content_type'] === 'text') ? 'selected' : ''; ?>>Text</option>
                                <option value="html"      <?php echo ($edit_content && $edit_content['content_type'] === 'html') ? 'selected' : ''; ?>>HTML (rich editor)</option>
                                <option value="json"      <?php echo ($edit_content && $edit_content['content_type'] === 'json') ? 'selected' : ''; ?>>JSON</option>
                                <option value="image_url" <?php echo ($edit_content && $edit_content['content_type'] === 'image_url') ? 'selected' : ''; ?>>Image URL</option>
                            </select>
                            <p class="text-xs text-gray-400 mt-1">Choose how this content should be stored and edited.</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Display Order</label>
                            <input type="number" name="display_order" min="0"
                                   value="<?php echo htmlspecialchars($edit_content['display_order'] ?? 0); ?>"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary">
                            <p class="text-xs text-gray-400 mt-1">Lower numbers appear first (0 = default).</p>
                        </div>
                    </div>

                    <div class="mt-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Content</label>
                        <textarea name="content" id="content" rows="10"
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary <?php echo (($edit_content['content_type'] ?? '') === 'json') ? 'font-mono' : ''; ?>"><?php echo htmlspecialchars_decode($edit_content['content'] ?? '', ENT_QUOTES); ?></textarea>

                        <!-- Live image preview (only relevant for image_url) -->
                        <div id="image_preview_wrap" class="mt-3 hidden">
                            <p class="text-xs text-gray-500 mb-1">Preview:</p>
                            <img id="image_preview" src="" alt="Preview" class="max-h-40 rounded border border-gray-200 bg-gray-50 p-2" onerror="this.classList.add('hidden')">
                        </div>

                        <p id="content_hint" class="text-xs text-gray-400 mt-1"></p>
                    </div>

                    <div class="mt-6">
                        <label class="flex items-center cursor-pointer">
                            <input type="checkbox" name="is_active" value="1"
                                   <?php echo ($edit_content && $edit_content['is_active']) || !$edit_content ? 'checked' : ''; ?>
                                   class="sr-only peer">
                            <span class="relative inline-flex items-center">
                                <span class="w-11 h-6 bg-gray-300 peer-checked:bg-primary rounded-full transition-colors"></span>
                                <span class="absolute left-0.5 top-0.5 w-5 h-5 bg-white rounded-full transition-transform peer-checked:translate-x-5"></span>
                            </span>
                            <span class="ml-3 text-sm text-gray-700">Active <span class="text-gray-400">(shown on the website)</span></span>
                        </label>
                    </div>

                    <div class="mt-8 flex flex-col sm:flex-row gap-3">
                        <button type="submit" class="bg-primary text-white px-6 py-2.5 rounded-lg hover:bg-secondary transition-colors inline-flex items-center justify-center">
                            <i class="fas fa-save mr-2"></i><?php echo $action === 'add' ? 'Add Content' : 'Save Changes'; ?>
                        </button>
                        <a href="pages.php" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-6 py-2.5 rounded-lg transition-colors inline-flex items-center justify-center">
                            <i class="fas fa-times mr-2"></i>Cancel
                        </a>
                    </div>
                </form>
            </div>

            <script>
            (function () {
                // Per-page known section names (for context-aware datalist filtering)
                var knownSections = <?php echo json_encode($known_section_names); ?>;
                var pageSelect = document.querySelector('select[name="page_name"]');
                var sectionInput = document.getElementById('section_name_input');
                var datalist = document.getElementById('section_suggestions');

                function rebuildDatalist() {
                    var page = pageSelect.value;
                    // Clear current options
                    datalist.innerHTML = '';
                    var names = knownSections[page] || [];
                    names.forEach(function (name) {
                        var opt = document.createElement('option');
                        opt.value = name;
                        datalist.appendChild(opt);
                    });
                }

                if (pageSelect && sectionInput && datalist) {
                    pageSelect.addEventListener('change', rebuildDatalist);
                    // Initialize for edit mode (only if section name not already in the list)
                    rebuildDatalist();
                    // Preserve the current value when editing
                    var current = sectionInput.value;
                    if (current) {
                        sectionInput.value = current;
                    }
                }
            })();

            (function () {
                var typeSelect = document.getElementById('content_type');
                var textarea = document.getElementById('content');
                var hint = document.getElementById('content_hint');
                var previewWrap = document.getElementById('image_preview_wrap');
                var previewImg = document.getElementById('image_preview');

                var hints = {
                    text:      'Plain text. Line breaks are preserved on the website.',
                    html:      'Rich text editor. Use the toolbar for formatting.',
                    json:      'Structured JSON. Must be valid JSON.',
                    image_url: 'Paste a direct image URL (e.g. uploads/images/photo.jpg).'
                };

                function refreshPreview() {
                    if (typeSelect.value === 'image_url') {
                        previewWrap.classList.remove('hidden');
                        var url = textarea.value.trim();
                        previewImg.src = url;
                        previewImg.classList.toggle('hidden', !url);
                    } else {
                        previewWrap.classList.add('hidden');
                    }
                }

                function applyEditor() {
                    var type = typeSelect.value;
                    hint.textContent = hints[type] || '';

                    // Toggle monospace for JSON
                    textarea.classList.toggle('font-mono', type === 'json');

                    // CKEditor only for HTML
                    if (type === 'html') {
                        if (typeof CKEDITOR !== 'undefined' && !CKEDITOR.instances.content) {
                            CKEDITOR.replace('content');
                        }
                    } else {
                        if (typeof CKEDITOR !== 'undefined' && CKEDITOR.instances.content) {
                            CKEDITOR.instances.content.destroy(true);
                        }
                    }
                    refreshPreview();
                }

                typeSelect.addEventListener('change', applyEditor);
                textarea.addEventListener('input', refreshPreview);

                // Initialize on load (CKEditor may still be loading from CDN)
                function init() {
                    if (typeSelect.value === 'html' && typeof CKEDITOR === 'undefined') {
                        return setTimeout(init, 100);
                    }
                    applyEditor();
                }
                init();
            })();
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
                    <form method="GET" action="pages.php" class="flex flex-col sm:flex-row gap-2">
                        <div class="flex-1 flex flex-col sm:flex-row gap-2">
                            <select name="filter_page" class="px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-primary focus:border-primary">
                                <option value="">All pages</option>
                                <?php foreach ($available_pages as $key => $info): ?>
                                    <option value="<?php echo $key; ?>" <?php echo (($_GET['filter_page'] ?? '') === $key) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($info[0]); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="relative flex-1">
                                <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                                <input type="text" name="q" value="<?php echo htmlspecialchars($_GET['q'] ?? ''); ?>"
                                       placeholder="Search section name or content…"
                                       class="w-full pl-9 pr-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-primary focus:border-primary">
                            </div>
                        </div>
                        <button type="submit" class="bg-primary text-white px-4 py-2 rounded-md hover:bg-secondary transition-colors text-sm inline-flex items-center justify-center">
                            <i class="fas fa-filter mr-1"></i>Filter
                        </button>
                        <?php if (!empty($_GET['filter_page']) || !empty($_GET['q'])): ?>
                        <a href="pages.php" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-200 transition-colors text-sm inline-flex items-center justify-center">
                            <i class="fas fa-times mr-1"></i>Clear
                        </a>
                        <?php endif; ?>
                    </form>
                </div>

                <div class="overflow-auto" style="max-height: 55vh;">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50 sticky top-0">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Page</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Section</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Content Preview</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (empty($all_content)): ?>
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center">
                                    <i class="fas fa-inbox text-4xl text-gray-300 mb-3"></i>
                                    <p class="text-gray-500 mb-2">No content found.</p>
                                    <a href="?action=add" class="text-primary hover:underline text-sm"><i class="fas fa-plus mr-1"></i>Add new content</a>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($all_content as $content): 
                                $page_label = $available_pages[$content['page_name']][0] ?? $content['page_name'];
                                $page_url   = $available_pages[$content['page_name']][1] ?? null;
                                $anchor     = getSectionAnchor($content['section_name'], $section_anchors);
                                $view_url   = $page_url ? $page_url . ($anchor ? '#' . $anchor : '') : null;
                                $ct_meta    = $content_types[$content['content_type']] ?? [$content['content_type'], 'bg-gray-100 text-gray-800'];
                                $preview = mb_substr(strip_tags($content['content'] ?? ''), 0, 60);
                            ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($page_label); ?></span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="text-sm text-gray-700 font-mono"><?php echo htmlspecialchars($content['section_name']); ?></span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 py-1 text-xs rounded-full <?php echo $ct_meta[1]; ?>"><?php echo htmlspecialchars($ct_meta[0]); ?></span>
                                    </td>
                                    <td class="px-6 py-4 max-w-xs">
                                        <span class="text-sm text-gray-500 truncate inline-block max-w-xs align-middle"><?php echo htmlspecialchars($preview); ?><?php echo mb_strlen(strip_tags($content['content'] ?? '')) > 60 ? '…' : ''; ?></span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="text-sm text-gray-900"><?php echo (int)$content['display_order']; ?></span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php if ($content['is_active']): ?>
                                            <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">Active</span>
                                        <?php else: ?>
                                            <span class="px-2 py-1 text-xs rounded-full bg-red-100 text-red-800">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <a href="?action=edit&id=<?php echo $content['id']; ?>" class="text-primary hover:text-secondary mr-3" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <?php if ($view_url): ?>
                                        <a href="<?php echo $view_url; ?>" target="_blank" class="text-gray-500 hover:text-gray-700 mr-3" title="View on site">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <?php endif; ?>
                                        <form method="POST" action="pages.php" class="inline" onsubmit="return confirm('Delete this content? This cannot be undone.');">
                                            <input type="hidden" name="delete_id" value="<?php echo $content['id']; ?>">
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
                    'total_items'   => $total_content,
                    'per_page'      => $per_page,
                    'base_query'    => $_GET,
                ]);
                ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
