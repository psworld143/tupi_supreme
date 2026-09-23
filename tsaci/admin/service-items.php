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
if ($status_param === 'saved')   $success = 'Service item saved successfully!';
if ($status_param === 'deleted') $success = 'Service item deleted successfully!';

$valid_sections = ['process' => 'Service Process step', 'features' => 'Why Choose feature'];

// Handle form submissions (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['delete_id'] ?? '') === '') {
    $section = sanitizeInput($_POST['section'] ?? 'features');
    if (!isset($valid_sections[$section])) $section = 'features';
    $title = sanitizeInput($_POST['title'] ?? '');
    $description = sanitizeInput($_POST['description'] ?? '');
    $icon = sanitizeInput($_POST['icon'] ?? '');
    $display_order = intval($_POST['display_order'] ?? 0);
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    if (empty($title)) {
        $error = 'Title is required.';
    } else {
        if ($action === 'add') {
            $stmt = $db->prepare("INSERT INTO service_items (section, title, description, icon, display_order, is_active, updated_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssiii", $section, $title, $description, $icon, $display_order, $is_active, $_SESSION['admin_id']);

            if ($stmt->execute()) {
                logActivity('create', 'service_items', $db->insert_id, "Created service item: {$title}");
                redirect('service-items.php?status=saved');
            } else {
                $error = 'Error adding item: ' . $stmt->error;
            }
        } elseif ($action === 'edit' && $id) {
            $stmt = $db->prepare("UPDATE service_items SET section = ?, title = ?, description = ?, icon = ?, display_order = ?, is_active = ?, updated_by = ? WHERE id = ?");
            $stmt->bind_param("ssssiiii", $section, $title, $description, $icon, $display_order, $is_active, $_SESSION['admin_id'], $id);

            if ($stmt->execute()) {
                logActivity('update', 'service_items', $id, "Updated service item: {$title}");
                redirect('service-items.php?status=saved');
            } else {
                $error = 'Error updating item: ' . $stmt->error;
            }
        }
    }
}

// Handle delete (POST only — safer than GET)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['delete_id'] ?? '') !== '') {
    $del_id = intval($_POST['delete_id']);
    $stmt = $db->prepare("DELETE FROM service_items WHERE id = ?");
    $stmt->bind_param("i", $del_id);
    if ($stmt->execute()) {
        logActivity('delete', 'service_items', $del_id, 'Deleted service item');
        redirect('service-items.php?status=deleted');
    } else {
        $error = 'Error deleting item: ' . $stmt->error;
    }
}

// Get item for edit
$edit_item = null;
if ($action === 'edit' && $id) {
    $stmt = $db->prepare("SELECT * FROM service_items WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $edit_item = $result->fetch_assoc();

    if (!$edit_item) {
        $error = 'Service item not found.';
        $action = 'list';
    }
}

// Get all items for list (with pagination + filtering + search)
$all_items = [];
$total_items = 0;
$total_pages = 1;
$current_page_num = 1;
$per_page = 10;

if ($action === 'list') {
    $current_page_num = max(1, intval($_GET['page'] ?? 1));
    $filter_section = $_GET['filter_section'] ?? '';
    $filter_status = $_GET['filter_status'] ?? '';
    $search_q = trim($_GET['q'] ?? '');

    // Build dynamic WHERE
    $where = [];
    $params = [];
    $types = '';
    if ($filter_section !== '' && isset($valid_sections[$filter_section])) {
        $where[] = 's.section = ?';
        $params[] = $filter_section;
        $types .= 's';
    }
    if ($filter_status === 'active') {
        $where[] = 's.is_active = 1';
    } elseif ($filter_status === 'inactive') {
        $where[] = 's.is_active = 0';
    }
    if ($search_q !== '') {
        $where[] = '(s.title LIKE ? OR s.description LIKE ?)';
        $like = '%' . $search_q . '%';
        $params[] = $like;
        $params[] = $like;
        $types .= 'ss';
    }
    $where_sql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

    // Total count
    $count_sql = "SELECT COUNT(*) as total FROM service_items s $where_sql";
    $count_stmt = $db->prepare($count_sql);
    if ($types && $count_stmt) {
        $count_stmt->bind_param($types, ...$params);
    }
    if ($count_stmt) {
        $count_stmt->execute();
        $total_items = $count_stmt->get_result()->fetch_assoc()['total'];
    }
    $total_pages = max(1, ceil($total_items / $per_page));
    if ($current_page_num > $total_pages) {
        $current_page_num = $total_pages;
    }
    $offset = ($current_page_num - 1) * $per_page;

    // List query
    $list_sql = "SELECT s.*, au.username as updated_by_name FROM service_items s LEFT JOIN admin_users au ON s.updated_by = au.id $where_sql ORDER BY s.section, s.display_order, s.title LIMIT ? OFFSET ?";
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
            $all_items[] = $row;
        }
    }
}

// Quick stats for the list header
$stats = ['total' => 0, 'process' => 0, 'features' => 0, 'active' => 0, 'inactive' => 0];
$st = $db->query("SELECT COUNT(*) total,
                 SUM(section = 'process') process_cnt,
                 SUM(section = 'features') features_cnt,
                 SUM(is_active) active
                 FROM service_items");
if ($st) {
    $row = $st->fetch_assoc();
    $stats['total'] = (int)$row['total'];
    $stats['process'] = (int)$row['process_cnt'];
    $stats['features'] = (int)$row['features_cnt'];
    $stats['active'] = (int)$row['active'];
    $stats['inactive'] = $stats['total'] - $stats['active'];
}

$view_url = '../services.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Service Items Management - <?php echo SITE_NAME; ?></title>
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
            .lg\:ml-64::-webkit-scrollbar-thumb { background-color: #c0ccc5; border-radius: 4px; border: 2px solid transparent; background-clip: padding-box; }
            .lg\:ml-64::-webkit-scrollbar-thumb:hover { background-color: #96a39b; }
        }
        .quick-pick { padding: 3px 8px; font-size: 11px; border: 1px solid #d1d5db; background: #fff; border-radius: 4px; cursor: pointer; color: #4b5563; }
        .quick-pick:hover { background: #f3f4f6; border-color: #9ca3af; }
        /* Matches services.php */
        .item-icon { background: #3d7a66; }
    </style>

    <!-- Main Content -->
    <div class="relative lg:ml-64 p-4 lg:p-8">
        <?php $logo_pulse_logo = '../uploads/images/tupi_supreme_logo.png'; $logo_pulse_mode = 'absolute'; include '../includes/logo_pulse_loader.php'; ?>

        <!-- Page Header -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3">
                <div>
                    <h1 class="text-3xl font-bold text-gray-800 flex items-center gap-3">
                        <i class="fas fa-stream text-primary"></i> Service Items Management
                    </h1>
                    <p class="text-sm text-gray-500 mt-1">Manage the "Our Service Process" steps and "Why Choose Our Services" rows on the services page.</p>
                </div>
                <?php if ($action === 'list'): ?>
                <a href="?action=add" class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-secondary transition-colors inline-flex items-center justify-center">
                    <i class="fas fa-plus mr-2"></i>Add New Item
                </a>
                <?php else: ?>
                <a href="service-items.php" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200 transition-colors inline-flex items-center justify-center">
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
                <h2 class="text-2xl font-bold mb-1"><?php echo $action === 'add' ? 'Add New' : 'Edit'; ?> Service Item</h2>
                <p class="text-sm text-gray-500 mb-4">Fields marked <span class="text-red-500">*</span> are required.</p>

                <!-- Info banner -->
                <div class="mb-6 p-3 rounded-lg bg-blue-50 border border-blue-200 flex items-start gap-2">
                    <i class="fas fa-info-circle text-blue-500 mt-0.5"></i>
                    <p class="text-sm text-blue-800">Items appear on the public <a href="<?php echo $view_url; ?>" target="_blank" class="underline hover:text-blue-900">Services page</a>. <strong>Process</strong> items render as numbered steps in the dark "Our Service Process" section; <strong>Features</strong> items render as rows in the "Why Choose Our Services" list. Sorted by display order.</p>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Left: form fields -->
                    <div>
                        <form method="POST" action="">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                <div class="sm:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Title <span class="text-red-500">*</span></label>
                                    <input type="text" name="title" id="title_input" required
                                           value="<?php echo htmlspecialchars($edit_item['title'] ?? ''); ?>"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary"
                                           placeholder="e.g., Assessment"
                                           oninput="updatePreview()">
                                    <p class="text-xs text-gray-400 mt-1">The step or feature heading. Must be unique within its section.</p>
                                </div>

                                <div class="sm:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Description <span class="text-gray-400 font-normal">(optional)</span></label>
                                    <input type="text" name="description" id="desc_input"
                                           value="<?php echo htmlspecialchars($edit_item['description'] ?? ''); ?>"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary"
                                           placeholder="e.g., We begin by assessing your needs…"
                                           oninput="updatePreview()">
                                    <p class="text-xs text-gray-400 mt-1">Small text shown under the title.</p>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Section <span class="text-red-500">*</span></label>
                                    <select name="section" id="section_input"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary"
                                            onchange="updatePreview()">
                                        <?php foreach ($valid_sections as $skey => $slabel): ?>
                                            <option value="<?php echo $skey; ?>" <?php echo ($edit_item && $edit_item['section'] === $skey) ? 'selected' : ''; ?>><?php echo $slabel; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <p class="text-xs text-gray-400 mt-1">Which block of the services page this item appears in.</p>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Icon <span class="text-gray-400 font-normal">(optional)</span></label>
                                    <div class="flex items-center gap-3">
                                        <input type="text" name="icon" id="icon_input"
                                               value="<?php echo htmlspecialchars($edit_item['icon'] ?? ''); ?>"
                                               class="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary font-mono"
                                               placeholder="fas fa-star"
                                               oninput="updateIconPreview(this.value)">
                                        <div id="icon_preview" class="flex items-center justify-center w-10 h-10 border border-gray-300 rounded-lg bg-gray-50 text-lg text-primary">
                                            <i class="fas fa-star"></i>
                                        </div>
                                    </div>
                                    <p class="text-xs text-gray-400 mt-1">Font Awesome icon class. <a href="https://fontawesome.com/v6/search?o=r&m=free" target="_blank" class="text-primary hover:underline">Browse icons</a>.</p>
                                    <div class="mt-2 flex flex-wrap gap-1">
                                        <button type="button" class="quick-pick" onclick="setIcon('fas fa-search')">search</button>
                                        <button type="button" class="quick-pick" onclick="setIcon('fas fa-lightbulb')">lightbulb</button>
                                        <button type="button" class="quick-pick" onclick="setIcon('fas fa-cogs')">cogs</button>
                                        <button type="button" class="quick-pick" onclick="setIcon('fas fa-chart-line')">chart-line</button>
                                        <button type="button" class="quick-pick" onclick="setIcon('fas fa-award')">award</button>
                                        <button type="button" class="quick-pick" onclick="setIcon('fas fa-clock')">clock</button>
                                        <button type="button" class="quick-pick" onclick="setIcon('fas fa-shield-alt')">shield</button>
                                        <button type="button" class="quick-pick" onclick="setIcon('fas fa-leaf')">leaf</button>
                                        <button type="button" class="quick-pick" onclick="setIcon('fas fa-handshake')">handshake</button>
                                        <button type="button" class="quick-pick" onclick="setIcon('fas fa-chart-bar')">chart-bar</button>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Display Order</label>
                                    <input type="number" name="display_order" min="0"
                                           value="<?php echo htmlspecialchars($edit_item['display_order'] ?? 0); ?>"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary">
                                    <p class="text-xs text-gray-400 mt-1">Lower numbers appear first within the section. Use 10, 20, 30…</p>
                                </div>
                            </div>

                            <div class="mt-6 space-y-4">
                                <label class="flex items-center cursor-pointer select-none">
                                    <input type="checkbox" name="is_active" value="1"
                                           <?php echo ($edit_item && $edit_item['is_active']) || !$edit_item ? 'checked' : ''; ?>
                                           class="sr-only peer">
                                    <span class="relative w-11 h-6 rounded-full bg-gray-300 transition-colors duration-300 ease-in-out
                                                 peer-checked:bg-primary peer-focus-visible:ring-2 peer-focus-visible:ring-primary/40 peer-focus-visible:ring-offset-2
                                                 after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:w-5 after:h-5
                                                 after:bg-white after:rounded-full after:shadow
                                                 after:transition-transform after:duration-300 after:ease-in-out
                                                 peer-checked:after:translate-x-5
                                                 hover:after:scale-110 active:after:scale-95"></span>
                                    <span class="ml-3 text-sm text-gray-700">Active <span class="text-gray-400">(shown on the services page)</span></span>
                                </label>
                            </div>

                            <div class="mt-8 flex flex-col sm:flex-row gap-3">
                                <button type="submit" class="bg-primary text-white px-6 py-2.5 rounded-lg hover:bg-secondary transition-colors inline-flex items-center justify-center">
                                    <i class="fas fa-save mr-2"></i><?php echo $action === 'add' ? 'Add Item' : 'Save Changes'; ?>
                                </button>
                                <a href="service-items.php" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-6 py-2.5 rounded-lg transition-colors inline-flex items-center justify-center">
                                    <i class="fas fa-times mr-2"></i>Cancel
                                </a>
                            </div>
                        </form>
                    </div>

                    <!-- Right: live preview -->
                    <div>
                        <p class="text-sm font-medium text-gray-700 mb-2 flex items-center gap-2">
                            <i class="fas fa-eye text-gray-400"></i> Live Preview
                            <span class="text-xs text-gray-400 font-normal">(how this item looks on the services page)</span>
                        </p>
                        <div class="rounded-lg p-6 bg-[#f0f4f1]">
                            <!-- Process-style preview (numbered step on dark background) -->
                            <div id="preview_process" class="rounded-2xl p-6 bg-[#23332c] text-white text-center <?php echo ($edit_item['section'] ?? 'features') === 'process' ? '' : 'hidden'; ?>">
                                <div class="item-icon w-16 h-16 rounded-2xl flex items-center justify-center mx-auto mb-4 relative">
                                    <i id="preview_icon_process" class="<?php echo htmlspecialchars($edit_item['icon'] ?? '') ?: 'fas fa-star'; ?> text-xl text-white"></i>
                                    <span class="absolute -top-2 -right-2 w-6 h-6 rounded-full bg-[#8bc34a] text-[#23332c] text-xs font-bold flex items-center justify-center">1</span>
                                </div>
                                <h5 id="preview_title_process" class="text-base font-semibold mb-2"><?php echo htmlspecialchars($edit_item['title'] ?? 'Step Title'); ?></h5>
                                <p id="preview_desc_process" class="text-sm text-white/70"><?php echo htmlspecialchars($edit_item['description'] ?? 'Step description appears here.'); ?></p>
                            </div>
                            <!-- Features-style preview (white row) -->
                            <div id="preview_features" class="rounded-2xl p-5 bg-white border border-[#e6ece8] flex items-center <?php echo ($edit_item['section'] ?? 'features') === 'features' ? '' : 'hidden'; ?>">
                                <div class="item-icon w-12 h-12 rounded-2xl flex items-center justify-center mr-4 flex-shrink-0">
                                    <i id="preview_icon_features" class="<?php echo htmlspecialchars($edit_item['icon'] ?? '') ?: 'fas fa-star'; ?> text-lg text-white"></i>
                                </div>
                                <div>
                                    <h5 id="preview_title_features" class="text-base font-semibold text-[#23332c] mb-1"><?php echo htmlspecialchars($edit_item['title'] ?? 'Feature Title'); ?></h5>
                                    <p id="preview_desc_features" class="text-sm text-[#7d8b84]"><?php echo htmlspecialchars($edit_item['description'] ?? 'Feature description appears here.'); ?></p>
                                </div>
                            </div>
                        </div>
                        <p class="text-xs text-gray-400 mt-2">Process items render as numbered steps on the dark section; feature items render as rows in the white list.</p>
                    </div>
                </div>
            </div>

            <script>
            function setIcon(i) { document.getElementById('icon_input').value = i; updateIconPreview(i); }

            function updateIconPreview(iconClass) {
                var cls = iconClass.trim() || 'fas fa-star';
                document.getElementById('icon_preview').innerHTML = '<i class="' + cls + '"></i>';
                document.getElementById('preview_icon_process').className = cls + ' text-xl text-white';
                document.getElementById('preview_icon_features').className = cls + ' text-lg text-white';
            }

            function updatePreview() {
                var title = document.getElementById('title_input').value || 'Item Title';
                var desc = document.getElementById('desc_input').value || 'Item description appears here.';
                var section = document.getElementById('section_input').value;

                document.getElementById('preview_title_process').textContent = title;
                document.getElementById('preview_desc_process').textContent = desc;
                document.getElementById('preview_title_features').textContent = title;
                document.getElementById('preview_desc_features').textContent = desc;
                document.getElementById('preview_process').classList.toggle('hidden', section !== 'process');
                document.getElementById('preview_features').classList.toggle('hidden', section !== 'features');
                updateIconPreview(document.getElementById('icon_input').value);
            }
            </script>

        <?php else: ?>
            <!-- List View -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <!-- Stats + Filters -->
                <div class="p-4 bg-gray-50 border-b">
                    <div class="flex flex-wrap items-center gap-2 mb-4">
                        <span class="px-3 py-1 text-xs rounded-full bg-gray-200 text-gray-700"><i class="fas fa-layer-group mr-1"></i><?php echo $stats['total']; ?> total</span>
                        <span class="px-3 py-1 text-xs rounded-full bg-blue-100 text-blue-800"><i class="fas fa-stream mr-1"></i><?php echo $stats['process']; ?> process</span>
                        <span class="px-3 py-1 text-xs rounded-full bg-blue-100 text-blue-800"><i class="fas fa-star mr-1"></i><?php echo $stats['features']; ?> features</span>
                        <span class="px-3 py-1 text-xs rounded-full bg-green-100 text-green-800"><i class="fas fa-check mr-1"></i><?php echo $stats['active']; ?> active</span>
                        <span class="px-3 py-1 text-xs rounded-full bg-red-100 text-red-800"><i class="fas fa-pause mr-1"></i><?php echo $stats['inactive']; ?> inactive</span>
                    </div>
                    <form method="GET" action="service-items.php" class="flex flex-col sm:flex-row gap-2">
                        <div class="flex-1 flex flex-col sm:flex-row gap-2">
                            <select name="filter_section" class="px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-primary focus:border-primary">
                                <option value="">All sections</option>
                                <option value="process" <?php echo (($_GET['filter_section'] ?? '') === 'process') ? 'selected' : ''; ?>>Process steps</option>
                                <option value="features" <?php echo (($_GET['filter_section'] ?? '') === 'features') ? 'selected' : ''; ?>>Why Choose features</option>
                            </select>
                            <select name="filter_status" class="px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-primary focus:border-primary">
                                <option value="">All statuses</option>
                                <option value="active" <?php echo (($_GET['filter_status'] ?? '') === 'active') ? 'selected' : ''; ?>>Active only</option>
                                <option value="inactive" <?php echo (($_GET['filter_status'] ?? '') === 'inactive') ? 'selected' : ''; ?>>Inactive only</option>
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
                        <?php if (!empty($_GET['filter_section']) || !empty($_GET['filter_status']) || !empty($_GET['q'])): ?>
                        <a href="service-items.php" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-200 transition-colors text-sm inline-flex items-center justify-center">
                            <i class="fas fa-times mr-1"></i>Clear
                        </a>
                        <?php endif; ?>
                    </form>
                </div>

                <div class="overflow-auto" style="max-height: 55vh;">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50 sticky top-0">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Icon</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Section</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Title</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (empty($all_items)): ?>
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center">
                                    <i class="fas fa-inbox text-4xl text-gray-300 mb-3"></i>
                                    <p class="text-gray-500 mb-2">No service items found.</p>
                                    <a href="?action=add" class="text-primary hover:underline text-sm"><i class="fas fa-plus mr-1"></i>Add new item</a>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($all_items as $item): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="w-9 h-9 rounded-lg item-icon flex items-center justify-center">
                                            <i class="<?php echo htmlspecialchars($item['icon'] ?: 'fas fa-star'); ?> text-white text-sm"></i>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php if ($item['section'] === 'process'): ?>
                                            <span class="px-2 py-1 text-xs rounded-full bg-[#23332c] text-white font-semibold">Process</span>
                                        <?php else: ?>
                                            <span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800 font-semibold">Features</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($item['title']); ?></span>
                                    </td>
                                    <td class="px-6 py-4 max-w-xs">
                                        <span class="text-sm text-gray-500 truncate inline-block max-w-xs align-middle"><?php echo htmlspecialchars($item['description'] ?: '—'); ?></span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="text-sm text-gray-900"><?php echo (int)$item['display_order']; ?></span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php if ($item['is_active']): ?>
                                            <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">Active</span>
                                        <?php else: ?>
                                            <span class="px-2 py-1 text-xs rounded-full bg-red-100 text-red-800">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <a href="?action=edit&id=<?php echo $item['id']; ?>" class="text-primary hover:text-secondary mr-3" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="<?php echo $view_url; ?>#<?php echo $item['section']; ?>" target="_blank" class="text-gray-500 hover:text-gray-700 mr-3" title="View on site">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <form method="POST" action="service-items.php" class="inline" onsubmit="return confirm('Delete this service item? This cannot be undone.');">
                                            <input type="hidden" name="delete_id" value="<?php echo $item['id']; ?>">
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
                    'total_items'   => $total_items,
                    'per_page'      => $per_page,
                    'base_query'    => $_GET,
                ]);
                ?>
            </div>

            <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
                <h3 class="font-semibold text-blue-900 mb-2">Quick Links</h3>
                <ul class="text-sm text-blue-800 space-y-1">
                    <li><a href="<?php echo $view_url; ?>#process" target="_blank" class="hover:underline"><i class="fas fa-external-link-alt mr-1"></i>View Services Page (process section)</a></li>
                    <li><a href="<?php echo $view_url; ?>#features" target="_blank" class="hover:underline"><i class="fas fa-external-link-alt mr-1"></i>View Services Page (features section)</a></li>
                    <li><a href="pages.php" class="hover:underline"><i class="fas fa-file-alt mr-1"></i>Edit section titles &amp; subtitles (Pages → services)</a></li>
                    <li><a href="index.php" class="hover:underline"><i class="fas fa-home mr-1"></i>Back to Admin Dashboard</a></li>
                </ul>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
