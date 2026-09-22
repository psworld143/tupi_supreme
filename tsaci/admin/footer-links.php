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
if ($status_param === 'saved')   $success = 'Footer link saved successfully!';
if ($status_param === 'deleted') $success = 'Footer link deleted successfully!';

// The public footer only renders these three categories
$categories = [
    'quick_links' => 'Quick Links',
    'products'    => 'Products',
    'legal'       => 'Legal (bottom bar)',
];

// Handle form submissions (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['delete_id'] ?? '') === '') {
    if (!verifyCsrfToken()) {
        $error = 'Security token expired or invalid. Please reload the page and try again.';
    } else {
        $category = sanitizeInput($_POST['category'] ?? '');
        $label = sanitizeInput($_POST['label'] ?? '');
        $url = trim($_POST['url'] ?? '');
        $display_order = intval($_POST['display_order'] ?? 0);
        $is_active = isset($_POST['is_active']) ? 1 : 0;

        if (!isset($categories[$category])) {
            $error = 'Please choose a valid link group.';
        } elseif (empty($label) || empty($url)) {
            $error = 'Label and URL are both required.';
        } else {
            if ($action === 'add') {
                $stmt = $db->prepare("INSERT INTO footer_links (category, label, url, display_order, is_active, updated_by) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sssiii", $category, $label, $url, $display_order, $is_active, $_SESSION['admin_id']);
                if ($stmt->execute()) {
                    logActivity('create', 'footer_links', $db->insert_id, "Added footer link ({$category}): {$label}");
                    redirect('footer-links.php?status=saved');
                } else {
                    $error = 'Error: ' . $stmt->error;
                }
            } elseif ($action === 'edit' && $id) {
                $stmt = $db->prepare("UPDATE footer_links SET category = ?, label = ?, url = ?, display_order = ?, is_active = ?, updated_by = ? WHERE id = ?");
                $stmt->bind_param("sssiiii", $category, $label, $url, $display_order, $is_active, $_SESSION['admin_id'], $id);
                if ($stmt->execute()) {
                    logActivity('update', 'footer_links', $id, "Updated footer link ({$category}): {$label}");
                    redirect('footer-links.php?status=saved');
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
        $stmt = $db->prepare("DELETE FROM footer_links WHERE id = ?");
        $stmt->bind_param("i", $del_id);
        if ($stmt->execute()) {
            logActivity('delete', 'footer_links', $del_id, 'Deleted footer link');
            redirect('footer-links.php?status=deleted');
        } else {
            $error = 'Error deleting footer link: ' . $stmt->error;
        }
    }
}

// Get link for edit
$edit_link = null;
if ($action === 'edit' && $id) {
    $stmt = $db->prepare("SELECT * FROM footer_links WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $edit_link = $result->fetch_assoc();
    if (!$edit_link) {
        $error = 'Footer link not found.';
        $action = 'list';
    }
}

// Get all links for list (with pagination + filtering + search)
$all_links = [];
$total_links = 0;
$total_pages = 1;
$current_page_num = 1;
$per_page = 10;

if ($action === 'list') {
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
    if (isset($categories[$filter_category])) {
        $where[] = 'category = ?';
        $params[] = $filter_category;
        $types .= 's';
    }
    if ($search_q !== '') {
        $where[] = '(label LIKE ? OR url LIKE ?)';
        $like = '%' . $search_q . '%';
        $params[] = $like;
        $params[] = $like;
        $types .= 'ss';
    }
    $where_sql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

    // Total count
    $count_stmt = $db->prepare("SELECT COUNT(*) as total FROM footer_links $where_sql");
    if ($types && $count_stmt) {
        $count_stmt->bind_param($types, ...$params);
    }
    if ($count_stmt) {
        $count_stmt->execute();
        $total_links = $count_stmt->get_result()->fetch_assoc()['total'];
    }
    $total_pages = max(1, ceil($total_links / $per_page));
    if ($current_page_num > $total_pages) {
        $current_page_num = $total_pages;
    }
    $offset = ($current_page_num - 1) * $per_page;

    // List query — group by category, then display order
    $list_stmt = $db->prepare("SELECT * FROM footer_links $where_sql ORDER BY FIELD(category, 'quick_links', 'products', 'legal'), display_order, id LIMIT ? OFFSET ?");
    $list_params = $params;
    $list_types = $types . 'ii';
    $list_params[] = $per_page;
    $list_params[] = $offset;
    if ($list_stmt) {
        $list_stmt->bind_param($list_types, ...$list_params);
        $list_stmt->execute();
        $result = $list_stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $all_links[] = $row;
        }
    }
}

// Quick stats for the list header
$stats = ['total' => 0, 'active' => 0, 'inactive' => 0, 'quick_links' => 0, 'products' => 0, 'legal' => 0];
$st = $db->query("SELECT COUNT(*) total, SUM(is_active) active, SUM(category='quick_links') quick_links, SUM(category='products') products, SUM(category='legal') legal FROM footer_links");
if ($st) {
    $row = $st->fetch_assoc();
    $stats['total'] = (int)$row['total'];
    $stats['active'] = (int)$row['active'];
    $stats['inactive'] = $stats['total'] - $stats['active'];
    $stats['quick_links'] = (int)$row['quick_links'];
    $stats['products'] = (int)$row['products'];
    $stats['legal'] = (int)$row['legal'];
}

// Footer links render in the footer on every public page
$view_url = '../index.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Footer Links - <?php echo SITE_NAME; ?></title>
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
    <div class="lg:ml-64 p-4 lg:p-8">
        <!-- Page Header -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3">
                <div>
                    <h1 class="text-3xl font-bold text-gray-800 flex items-center gap-3">
                        <i class="fas fa-link text-primary"></i> Footer Links
                    </h1>
                    <p class="text-sm text-gray-500 mt-1">Manage the link groups in the footer of every page.</p>
                </div>
                <?php if ($action === 'list'): ?>
                <a href="?action=add" class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-secondary transition-colors inline-flex items-center justify-center">
                    <i class="fas fa-plus mr-2"></i>Add New Link
                </a>
                <?php else: ?>
                <a href="footer-links.php" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200 transition-colors inline-flex items-center justify-center">
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
                <h2 class="text-2xl font-bold mb-1"><?php echo $action === 'add' ? 'Add New' : 'Edit'; ?> Footer Link</h2>
                <p class="text-sm text-gray-500 mb-4">Fields marked <span class="text-red-500">*</span> are required.</p>

                <!-- Info banner -->
                <div class="mb-6 p-3 rounded-lg bg-blue-50 border border-blue-200 flex items-start gap-2">
                    <i class="fas fa-info-circle text-blue-500 mt-0.5"></i>
                    <div class="text-sm text-blue-800">
                        <p>The public footer (see the <a href="<?php echo $view_url; ?>" target="_blank" class="underline hover:text-blue-900">homepage footer</a>) has three link groups:</p>
                        <ul class="list-disc ml-5 mt-1 space-y-0.5">
                            <li><strong>Quick Links</strong> — the "Quick Links" column (site navigation)</li>
                            <li><strong>Products</strong> — the "Products" column</li>
                            <li><strong>Legal</strong> — links in the bottom bar next to the copyright line</li>
                        </ul>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Left: form fields -->
                    <div>
                        <form method="POST" action="">
                            <?php echo csrfTokenField(); ?>
                            <div class="space-y-6">
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Link Group <span class="text-red-500">*</span></label>
                                        <select name="category" id="category_input" required
                                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary"
                                                onchange="updatePreview()">
                                            <?php foreach ($categories as $key => $label_opt): ?>
                                                <option value="<?php echo $key; ?>" <?php echo (($edit_link['category'] ?? 'quick_links') === $key) ? 'selected' : ''; ?>><?php echo $label_opt; ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <p class="text-xs text-gray-400 mt-1">Controls which footer column the link appears in.</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Label <span class="text-red-500">*</span></label>
                                        <input type="text" name="label" id="label_input" required maxlength="200"
                                               value="<?php echo htmlspecialchars($edit_link['label'] ?? ''); ?>"
                                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary"
                                               placeholder="e.g., About Us"
                                               oninput="updatePreview()">
                                        <p class="text-xs text-gray-400 mt-1">The link text shown in the footer.</p>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">URL <span class="text-red-500">*</span></label>
                                    <input type="text" name="url" id="url_input" required maxlength="500"
                                           value="<?php echo htmlspecialchars($edit_link['url'] ?? ''); ?>"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary"
                                           placeholder="e.g., about.php or products.php#granulated or https://…">
                                    <p class="text-xs text-gray-400 mt-1">Relative page (about.php), page anchor (products.php#granulated), or full URL.</p>
                                    <div class="mt-2 flex flex-wrap gap-1">
                                        <button type="button" class="quick-pick" onclick="setUrl('index.php')">index.php</button>
                                        <button type="button" class="quick-pick" onclick="setUrl('about.php')">about.php</button>
                                        <button type="button" class="quick-pick" onclick="setUrl('products.php')">products.php</button>
                                        <button type="button" class="quick-pick" onclick="setUrl('services.php')">services.php</button>
                                        <button type="button" class="quick-pick" onclick="setUrl('case-studies.php')">case-studies.php</button>
                                        <button type="button" class="quick-pick" onclick="setUrl('resources.php')">resources.php</button>
                                        <button type="button" class="quick-pick" onclick="setUrl('contact.php')">contact.php</button>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Display Order</label>
                                        <input type="number" name="display_order" min="0"
                                               value="<?php echo htmlspecialchars($edit_link['display_order'] ?? 0); ?>"
                                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary">
                                        <p class="text-xs text-gray-400 mt-1">Lower numbers appear first within each group.</p>
                                    </div>
                                    <div class="flex items-center">
                                        <label class="flex items-center cursor-pointer select-none">
                                            <input type="checkbox" name="is_active" value="1"
                                                   <?php echo ($edit_link && $edit_link['is_active']) || !$edit_link ? 'checked' : ''; ?>
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
                                    <i class="fas fa-save mr-2"></i><?php echo $action === 'add' ? 'Add Link' : 'Save Changes'; ?>
                                </button>
                                <a href="footer-links.php" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-6 py-2.5 rounded-lg transition-colors inline-flex items-center justify-center">
                                    <i class="fas fa-times mr-2"></i>Cancel
                                </a>
                            </div>
                        </form>
                    </div>

                    <!-- Right: live preview -->
                    <div>
                        <p class="text-sm font-medium text-gray-700 mb-2 flex items-center gap-2">
                            <i class="fas fa-eye text-gray-400"></i> Live Preview
                            <span class="text-xs text-gray-400 font-normal">(footer column)</span>
                        </p>
                        <div class="rounded-2xl p-8" style="background: #23332c;">
                            <h6 id="preview_heading" class="text-sm font-semibold uppercase tracking-wider mb-4" style="color: rgba(255,255,255,0.6);">Quick Links</h6>
                            <ul class="space-y-2.5">
                                <li><a id="preview_link" class="text-sm transition-colors hover:text-[#8bc34a]" style="color: rgba(255,255,255,0.75);" onclick="return false;" href="#"><?php echo htmlspecialchars($edit_link['label'] ?? 'Link label'); ?></a></li>
                                <li><span class="text-sm" style="color: rgba(255,255,255,0.35);">About Us</span></li>
                                <li><span class="text-sm" style="color: rgba(255,255,255,0.35);">Services</span></li>
                            </ul>
                        </div>
                        <p class="text-xs text-gray-400 mt-2">The preview mirrors the link columns in the public footer.</p>
                    </div>
                </div>
            </div>

            <script>
            var groupHeadings = {
                quick_links: 'Quick Links',
                products: 'Products',
                legal: 'Legal'
            };

            // Quick-pick helper
            function setUrl(url) {
                document.getElementById('url_input').value = url;
            }

            // Live preview
            function updatePreview() {
                var category = document.getElementById('category_input').value;
                var label = document.getElementById('label_input').value || 'Link label';

                document.getElementById('preview_heading').textContent = groupHeadings[category] || 'Quick Links';
                document.getElementById('preview_link').textContent = label;
            }
            </script>

        <?php else: ?>
            <!-- List View -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <!-- Stats + Filters -->
                <div class="p-4 bg-gray-50 border-b">
                    <div class="flex flex-wrap items-center gap-2 mb-4">
                        <span class="px-3 py-1 text-xs rounded-full bg-gray-200 text-gray-700"><i class="fas fa-layer-group mr-1"></i><?php echo $stats['total']; ?> total</span>
                        <span class="px-3 py-1 text-xs rounded-full bg-blue-100 text-blue-800"><i class="fas fa-link mr-1"></i><?php echo $stats['quick_links']; ?> quick links</span>
                        <span class="px-3 py-1 text-xs rounded-full bg-blue-100 text-blue-800"><i class="fas fa-cube mr-1"></i><?php echo $stats['products']; ?> products</span>
                        <span class="px-3 py-1 text-xs rounded-full bg-blue-100 text-blue-800"><i class="fas fa-gavel mr-1"></i><?php echo $stats['legal']; ?> legal</span>
                        <span class="px-3 py-1 text-xs rounded-full bg-red-100 text-red-800"><i class="fas fa-pause mr-1"></i><?php echo $stats['inactive']; ?> inactive</span>
                    </div>
                    <form method="GET" action="footer-links.php" class="flex flex-col sm:flex-row gap-2">
                        <div class="flex-1 flex flex-col sm:flex-row gap-2">
                            <select name="filter_category" class="px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-primary focus:border-primary">
                                <option value="">All groups</option>
                                <?php foreach ($categories as $key => $label_opt): ?>
                                    <option value="<?php echo $key; ?>" <?php echo (($_GET['filter_category'] ?? '') === $key) ? 'selected' : ''; ?>><?php echo $label_opt; ?></option>
                                <?php endforeach; ?>
                            </select>
                            <select name="filter_status" class="px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-primary focus:border-primary">
                                <option value="">All statuses</option>
                                <option value="active" <?php echo (($_GET['filter_status'] ?? '') === 'active') ? 'selected' : ''; ?>>Active only</option>
                                <option value="inactive" <?php echo (($_GET['filter_status'] ?? '') === 'inactive') ? 'selected' : ''; ?>>Inactive only</option>
                            </select>
                            <div class="relative flex-1">
                                <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                                <input type="text" name="q" value="<?php echo htmlspecialchars($_GET['q'] ?? ''); ?>"
                                       placeholder="Search labels or URLs…"
                                       class="w-full pl-9 pr-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-primary focus:border-primary">
                            </div>
                        </div>
                        <button type="submit" class="bg-primary text-white px-4 py-2 rounded-md hover:bg-secondary transition-colors text-sm inline-flex items-center justify-center">
                            <i class="fas fa-filter mr-1"></i>Filter
                        </button>
                        <?php if (!empty($_GET['filter_category']) || !empty($_GET['filter_status']) || !empty($_GET['q'])): ?>
                        <a href="footer-links.php" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-200 transition-colors text-sm inline-flex items-center justify-center">
                            <i class="fas fa-times mr-1"></i>Clear
                        </a>
                        <?php endif; ?>
                    </form>
                </div>

                <div class="overflow-auto" style="max-height: 55vh;">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50 sticky top-0">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Group</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Label</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">URL</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (empty($all_links)): ?>
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center">
                                    <i class="fas fa-link text-4xl text-gray-300 mb-3"></i>
                                    <p class="text-gray-500 mb-2">No footer links found.</p>
                                    <a href="?action=add" class="text-primary hover:underline text-sm"><i class="fas fa-plus mr-1"></i>Add new link</a>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($all_links as $link): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 text-sm">
                                        <span class="px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-700"><?php echo htmlspecialchars($categories[$link['category']] ?? $link['category']); ?></span>
                                    </td>
                                    <td class="px-6 py-4 text-sm font-medium text-gray-900"><?php echo htmlspecialchars($link['label']); ?></td>
                                    <td class="px-6 py-4 text-sm text-gray-500 max-w-xs truncate" title="<?php echo htmlspecialchars($link['url']); ?>"><?php echo htmlspecialchars($link['url']); ?></td>
                                    <td class="px-6 py-4 text-sm text-gray-900"><?php echo (int)$link['display_order']; ?></td>
                                    <td class="px-6 py-4">
                                        <?php if ($link['is_active']): ?>
                                            <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">Active</span>
                                        <?php else: ?>
                                            <span class="px-2 py-1 text-xs rounded-full bg-red-100 text-red-800">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 text-sm font-medium whitespace-nowrap">
                                        <a href="?action=edit&id=<?php echo $link['id']; ?>" class="text-primary hover:text-secondary mr-3" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="<?php echo $view_url; ?>" target="_blank" class="text-gray-500 hover:text-gray-700 mr-3" title="View on site">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <form method="POST" action="footer-links.php" class="inline" onsubmit="return confirm('Delete this footer link? This cannot be undone.');">
                                            <?php echo csrfTokenField(); ?>
                                            <input type="hidden" name="delete_id" value="<?php echo $link['id']; ?>">
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
                    'total_items'   => $total_links,
                    'per_page'      => $per_page,
                    'base_query'    => $_GET,
                ]);
                ?>
            </div>

            <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
                <h3 class="font-semibold text-blue-900 mb-2">Quick Links</h3>
                <ul class="text-sm text-blue-800 space-y-1">
                    <li><a href="<?php echo $view_url; ?>" target="_blank" class="hover:underline"><i class="fas fa-external-link-alt mr-1"></i>View Homepage Footer</a></li>
                    <li><a href="social-media.php" class="hover:underline"><i class="fas fa-share-alt mr-1"></i>Manage Social Media</a></li>
                    <li><a href="site-settings.php" class="hover:underline"><i class="fas fa-sliders-h mr-1"></i>Site Settings</a></li>
                    <li><a href="index.php" class="hover:underline"><i class="fas fa-home mr-1"></i>Back to Admin Dashboard</a></li>
                </ul>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
