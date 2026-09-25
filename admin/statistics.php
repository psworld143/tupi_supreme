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

// Status flash from PRG redirect (avoids form resubmission on refresh)
$status_param = $_GET['status'] ?? '';
if ($status_param === 'saved')   $success = 'Statistic saved successfully!';
if ($status_param === 'deleted') $success = 'Statistic deleted successfully!';

// Handle form submissions (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['delete_id'] ?? '') === '') {
    $label = sanitizeInput($_POST['label'] ?? '');
    $value = sanitizeInput($_POST['value'] ?? '');
    $description = sanitizeInput($_POST['description'] ?? '');
    $icon = sanitizeInput($_POST['icon'] ?? '');
    $display_order = intval($_POST['display_order'] ?? 0);
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    if (empty($label) || empty($value)) {
        $error = 'Label and value are required.';
    } else {
        if ($action === 'add') {
            $stmt = $db->prepare("INSERT INTO statistics (label, value, description, icon, display_order, is_active, updated_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssiii", $label, $value, $description, $icon, $display_order, $is_active, $_SESSION['admin_id']);

            if ($stmt->execute()) {
                logActivity('create', 'statistics', $db->insert_id, "Created statistic: {$label} - {$value}");
                redirect('statistics.php?status=saved');
            } else {
                $error = 'Error adding statistic: ' . $stmt->error;
            }
        } elseif ($action === 'edit' && $id) {
            $stmt = $db->prepare("UPDATE statistics SET label = ?, value = ?, description = ?, icon = ?, display_order = ?, is_active = ?, updated_by = ? WHERE id = ?");
            $stmt->bind_param("ssssiiii", $label, $value, $description, $icon, $display_order, $is_active, $_SESSION['admin_id'], $id);

            if ($stmt->execute()) {
                logActivity('update', 'statistics', $id, "Updated statistic: {$label} - {$value}");
                redirect('statistics.php?status=saved');
            } else {
                $error = 'Error updating statistic: ' . $stmt->error;
            }
        }
    }
}

// Handle delete (POST only — safer than GET)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['delete_id'] ?? '') !== '') {
    $del_id = intval($_POST['delete_id']);
    $stmt = $db->prepare("DELETE FROM statistics WHERE id = ?");
    $stmt->bind_param("i", $del_id);
    if ($stmt->execute()) {
        logActivity('delete', 'statistics', $del_id, 'Deleted statistic');
        redirect('statistics.php?status=deleted');
    } else {
        $error = 'Error deleting statistic: ' . $stmt->error;
    }
}

// Get statistic for edit
$edit_stat = null;
if ($action === 'edit' && $id) {
    $stmt = $db->prepare("SELECT * FROM statistics WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $edit_stat = $result->fetch_assoc();

    if (!$edit_stat) {
        $error = 'Statistic not found.';
        $action = 'list';
    }
}

// Get all statistics for list (with pagination + filtering + search)
$all_stats = [];
$total_stats = 0;
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
        $where[] = 's.is_active = 1';
    } elseif ($filter_status === 'inactive') {
        $where[] = 's.is_active = 0';
    }
    if ($search_q !== '') {
        $where[] = '(s.label LIKE ? OR s.value LIKE ? OR s.description LIKE ?)';
        $like = '%' . $search_q . '%';
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
        $types .= 'sss';
    }
    $where_sql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

    // Total count
    $count_sql = "SELECT COUNT(*) as total FROM statistics s $where_sql";
    $count_stmt = $db->prepare($count_sql);
    if ($types && $count_stmt) {
        $count_stmt->bind_param($types, ...$params);
    }
    if ($count_stmt) {
        $count_stmt->execute();
        $total_stats = $count_stmt->get_result()->fetch_assoc()['total'];
    }
    $total_pages = max(1, ceil($total_stats / $per_page));
    if ($current_page_num > $total_pages) {
        $current_page_num = $total_pages;
    }
    $offset = ($current_page_num - 1) * $per_page;

    // List query
    $list_sql = "SELECT s.*, au.username as updated_by_name FROM statistics s LEFT JOIN admin_users au ON s.updated_by = au.id $where_sql ORDER BY s.display_order, s.label LIMIT ? OFFSET ?";
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
            $all_stats[] = $row;
        }
    }
}

// Quick stats for the list header
$stats = ['total' => 0, 'active' => 0, 'inactive' => 0];
$st = $db->query("SELECT COUNT(*) total, SUM(is_active) active FROM statistics");
if ($st) {
    $row = $st->fetch_assoc();
    $stats['total'] = (int)$row['total'];
    $stats['active'] = (int)$row['active'];
    $stats['inactive'] = $stats['total'] - $stats['active'];
}

// All statistics render in the same stats section on the homepage
$view_url = '../index.php#stats';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statistics Management - <?php echo SITE_NAME; ?></title>
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
        /* Quick-pick suggestion buttons */
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
                        <i class="fas fa-chart-bar text-primary"></i> Statistics Management
                    </h1>
                    <p class="text-sm text-gray-500 mt-1">Manage the homepage statistics counters (e.g., "25+ Years Experience").</p>
                </div>
                <?php if ($action === 'list'): ?>
                <a href="?action=add" class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-secondary transition-colors inline-flex items-center justify-center">
                    <i class="fas fa-plus mr-2"></i>Add New Statistic
                </a>
                <?php else: ?>
                <a href="statistics.php" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200 transition-colors inline-flex items-center justify-center">
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
                <h2 class="text-2xl font-bold mb-1"><?php echo $action === 'add' ? 'Add New' : 'Edit'; ?> Statistic</h2>
                <p class="text-sm text-gray-500 mb-4">Fields marked <span class="text-red-500">*</span> are required.</p>

                <!-- Info banner -->
                <div class="mb-6 p-3 rounded-lg bg-blue-50 border border-blue-200 flex items-start gap-2">
                    <i class="fas fa-info-circle text-blue-500 mt-0.5"></i>
                    <p class="text-sm text-blue-800">Statistics appear as counters in the <strong>stats section</strong> on the public <a href="<?php echo $view_url; ?>" target="_blank" class="underline hover:text-blue-900">homepage</a>. They're shown in a 4-column grid, sorted by display order (left to right).</p>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Left: form fields -->
                    <div>
                        <form method="POST" action="">
                            <?php echo csrfTokenField(); ?>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Value <span class="text-red-500">*</span></label>
                                    <input type="text" name="value" id="value_input" required
                                           value="<?php echo htmlspecialchars($edit_stat['value'] ?? ''); ?>"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary"
                                           placeholder="e.g., 25+"
                                           oninput="updatePreview()">
                                    <p class="text-xs text-gray-400 mt-1">The big number shown. Can include <code class="bg-gray-100 px-1 rounded">+</code>, <code class="bg-gray-100 px-1 rounded">%</code>, <code class="bg-gray-100 px-1 rounded">/</code>, etc.</p>
                                    <div class="mt-2 flex flex-wrap gap-1">
                                        <button type="button" class="quick-pick" onclick="setVal('25+')">25+</button>
                                        <button type="button" class="quick-pick" onclick="setVal('50+')">50+</button>
                                        <button type="button" class="quick-pick" onclick="setVal('100+')">100+</button>
                                        <button type="button" class="quick-pick" onclick="setVal('500+')">500+</button>
                                        <button type="button" class="quick-pick" onclick="setVal('1000+')">1000+</button>
                                        <button type="button" class="quick-pick" onclick="setVal('99%')">99%</button>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Label <span class="text-red-500">*</span></label>
                                    <input type="text" name="label" id="label_input" required
                                           value="<?php echo htmlspecialchars($edit_stat['label'] ?? ''); ?>"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary"
                                           placeholder="e.g., Years Experience"
                                           oninput="updatePreview()">
                                    <p class="text-xs text-gray-400 mt-1">The caption shown below the value (uppercase on the site).</p>
                                    <div class="mt-2 flex flex-wrap gap-1">
                                        <button type="button" class="quick-pick" onclick="setLabel('Years Experience')">Years Experience</button>
                                        <button type="button" class="quick-pick" onclick="setLabel('Projects Completed')">Projects Completed</button>
                                        <button type="button" class="quick-pick" onclick="setLabel('Happy Clients')">Happy Clients</button>
                                        <button type="button" class="quick-pick" onclick="setLabel('Team Members')">Team Members</button>
                                        <button type="button" class="quick-pick" onclick="setLabel('Tons Produced')">Tons Produced</button>
                                    </div>
                                </div>

                                <div class="sm:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Description <span class="text-gray-400 font-normal">(optional)</span></label>
                                    <input type="text" name="description" id="desc_input"
                                           value="<?php echo htmlspecialchars($edit_stat['description'] ?? ''); ?>"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary"
                                           placeholder="e.g., Years of industry experience"
                                           oninput="updatePreview()">
                                    <p class="text-xs text-gray-400 mt-1">Optional smaller text shown below the label.</p>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Icon <span class="text-gray-400 font-normal">(optional)</span></label>
                                    <div class="flex items-center gap-3">
                                        <input type="text" name="icon" id="icon_input"
                                               value="<?php echo htmlspecialchars($edit_stat['icon'] ?? ''); ?>"
                                               class="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary font-mono"
                                               placeholder="fas fa-chart-bar"
                                               oninput="updateIconPreview(this.value)">
                                        <div id="icon_preview" class="flex items-center justify-center w-10 h-10 border border-gray-300 rounded-lg bg-gray-50 text-lg text-primary">
                                            <i class="fas fa-chart-bar"></i>
                                        </div>
                                    </div>
                                    <p class="text-xs text-gray-400 mt-1">Font Awesome icon class. <a href="https://fontawesome.com/v6/search?o=r&m=free" target="_blank" class="text-primary hover:underline">Browse icons</a>. Note: not currently displayed on the homepage, but stored for future use.</p>
                                    <div class="mt-2 flex flex-wrap gap-1">
                                        <button type="button" class="quick-pick" onclick="setIcon('fas fa-chart-bar')">chart-bar</button>
                                        <button type="button" class="quick-pick" onclick="setIcon('fas fa-calendar')">calendar</button>
                                        <button type="button" class="quick-pick" onclick="setIcon('fas fa-users')">users</button>
                                        <button type="button" class="quick-pick" onclick="setIcon('fas fa-industry')">industry</button>
                                        <button type="button" class="quick-pick" onclick="setIcon('fas fa-leaf')">leaf</button>
                                        <button type="button" class="quick-pick" onclick="setIcon('fas fa-globe')">globe</button>
                                        <button type="button" class="quick-pick" onclick="setIcon('fas fa-award')">award</button>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Display Order</label>
                                    <input type="number" name="display_order" min="0"
                                           value="<?php echo htmlspecialchars($edit_stat['display_order'] ?? 0); ?>"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary">
                                    <p class="text-xs text-gray-400 mt-1">Lower numbers appear first (left to right). Use 10, 20, 30… to leave room for inserts.</p>
                                </div>
                            </div>

                            <div class="mt-6">
                                <label class="flex items-center cursor-pointer select-none">
                                    <input type="checkbox" name="is_active" value="1"
                                           <?php echo ($edit_stat && $edit_stat['is_active']) || !$edit_stat ? 'checked' : ''; ?>
                                           class="sr-only peer">
                                    <span class="relative w-11 h-6 rounded-full bg-gray-300 transition-colors duration-300 ease-in-out
                                                 peer-checked:bg-primary peer-focus-visible:ring-2 peer-focus-visible:ring-primary/40 peer-focus-visible:ring-offset-2
                                                 after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:w-5 after:h-5
                                                 after:bg-white after:rounded-full after:shadow
                                                 after:transition-transform after:duration-300 after:ease-in-out
                                                 peer-checked:after:translate-x-5
                                                 hover:after:scale-110 active:after:scale-95"></span>
                                    <span class="ml-3 text-sm text-gray-700">Active <span class="text-gray-400">(shown on the homepage)</span></span>
                                </label>
                            </div>

                            <div class="mt-8 flex flex-col sm:flex-row gap-3">
                                <button type="submit" class="bg-primary text-white px-6 py-2.5 rounded-lg hover:bg-secondary transition-colors inline-flex items-center justify-center">
                                    <i class="fas fa-save mr-2"></i><?php echo $action === 'add' ? 'Add Statistic' : 'Save Changes'; ?>
                                </button>
                                <a href="statistics.php" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-6 py-2.5 rounded-lg transition-colors inline-flex items-center justify-center">
                                    <i class="fas fa-times mr-2"></i>Cancel
                                </a>
                            </div>
                        </form>
                    </div>

                    <!-- Right: live preview -->
                    <div>
                        <p class="text-sm font-medium text-gray-700 mb-2 flex items-center gap-2">
                            <i class="fas fa-eye text-gray-400"></i> Live Preview
                            <span class="text-xs text-gray-400 font-normal">(how this stat looks on the homepage)</span>
                        </p>
                        <!-- Approximation of the dark stats section on index.php -->
                        <div class="rounded-lg p-6 bg-[#23332c] text-white">
                            <div class="grid grid-cols-1 gap-4">
                                <div class="text-center">
                                    <div class="stat-value text-4xl lg:text-5xl font-bold mb-2" id="preview_value"><?php echo htmlspecialchars($edit_stat['value'] ?? '25+'); ?></div>
                                    <div class="text-sm font-medium uppercase tracking-wider text-[#8bc34a]" id="preview_label"><?php echo htmlspecialchars($edit_stat['label'] ?? 'Years Experience'); ?></div>
                                    <div class="text-sm text-white/60 mt-2 <?php echo empty($edit_stat['description'] ?? '') ? 'hidden' : ''; ?>" id="preview_desc_wrap">
                                        <span id="preview_desc"><?php echo htmlspecialchars($edit_stat['description'] ?? ''); ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <p class="text-xs text-gray-400 mt-2">On the homepage, up to 4 stats appear side by side in a row.</p>
                    </div>
                </div>
            </div>

            <script>
            // Quick-pick helpers
            function setVal(v) { document.getElementById('value_input').value = v; updatePreview(); }
            function setLabel(l) { document.getElementById('label_input').value = l; updatePreview(); }
            function setIcon(i) { document.getElementById('icon_input').value = i; updateIconPreview(i); }

            // Live icon preview
            function updateIconPreview(iconClass) {
                var preview = document.getElementById('icon_preview');
                preview.innerHTML = '<i class="' + iconClass + '"></i>';
            }

            // Live preview of the stat card
            function updatePreview() {
                var value = document.getElementById('value_input').value || '25+';
                var label = document.getElementById('label_input').value || 'Years Experience';
                var desc = document.getElementById('desc_input').value;
                document.getElementById('preview_value').textContent = value;
                document.getElementById('preview_label').textContent = label;
                var descWrap = document.getElementById('preview_desc_wrap');
                if (desc.trim()) {
                    document.getElementById('preview_desc').textContent = desc;
                    descWrap.classList.remove('hidden');
                } else {
                    descWrap.classList.add('hidden');
                }
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
                    <form method="GET" action="statistics.php" class="flex flex-col sm:flex-row gap-2">
                        <div class="flex-1 flex flex-col sm:flex-row gap-2">
                            <select name="filter_status" class="px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-primary focus:border-primary">
                                <option value="">All statuses</option>
                                <option value="active" <?php echo (($_GET['filter_status'] ?? '') === 'active') ? 'selected' : ''; ?>>Active only</option>
                                <option value="inactive" <?php echo (($_GET['filter_status'] ?? '') === 'inactive') ? 'selected' : ''; ?>>Inactive only</option>
                            </select>
                            <div class="relative flex-1">
                                <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                                <input type="text" name="q" value="<?php echo htmlspecialchars($_GET['q'] ?? ''); ?>"
                                       placeholder="Search value, label, or description…"
                                       class="w-full pl-9 pr-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-primary focus:border-primary">
                            </div>
                        </div>
                        <button type="submit" class="bg-primary text-white px-4 py-2 rounded-md hover:bg-secondary transition-colors text-sm inline-flex items-center justify-center">
                            <i class="fas fa-filter mr-1"></i>Filter
                        </button>
                        <?php if (!empty($_GET['filter_status']) || !empty($_GET['q'])): ?>
                        <a href="statistics.php" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-200 transition-colors text-sm inline-flex items-center justify-center">
                            <i class="fas fa-times mr-1"></i>Clear
                        </a>
                        <?php endif; ?>
                    </form>
                </div>

                <div class="overflow-auto" style="max-height: 55vh;">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50 sticky top-0">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Value</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Label</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (empty($all_stats)): ?>
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center">
                                    <i class="fas fa-inbox text-4xl text-gray-300 mb-3"></i>
                                    <p class="text-gray-500 mb-2">No statistics found.</p>
                                    <a href="?action=add" class="text-primary hover:underline text-sm"><i class="fas fa-plus mr-1"></i>Add new statistic</a>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($all_stats as $stat): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="text-lg font-bold text-primary"><?php echo htmlspecialchars($stat['value']); ?></span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($stat['label']); ?></span>
                                    </td>
                                    <td class="px-6 py-4 max-w-xs">
                                        <span class="text-sm text-gray-500 truncate inline-block max-w-xs align-middle"><?php echo htmlspecialchars($stat['description'] ?: '—'); ?></span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="text-sm text-gray-900"><?php echo (int)$stat['display_order']; ?></span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php if ($stat['is_active']): ?>
                                            <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">Active</span>
                                        <?php else: ?>
                                            <span class="px-2 py-1 text-xs rounded-full bg-red-100 text-red-800">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <a href="?action=edit&id=<?php echo $stat['id']; ?>" class="text-primary hover:text-secondary mr-3" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="<?php echo $view_url; ?>" target="_blank" class="text-gray-500 hover:text-gray-700 mr-3" title="View on site">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <form method="POST" action="statistics.php" class="inline" onsubmit="return confirm('Delete this statistic? This cannot be undone.');">
                                            <?php echo csrfTokenField(); ?>
                                            <input type="hidden" name="delete_id" value="<?php echo $stat['id']; ?>">
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
                    'total_items'   => $total_stats,
                    'per_page'      => $per_page,
                    'base_query'    => $_GET,
                ]);
                ?>
            </div>

            <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
                <h3 class="font-semibold text-blue-900 mb-2">Quick Links</h3>
                <ul class="text-sm text-blue-800 space-y-1">
                    <li><a href="<?php echo $view_url; ?>" target="_blank" class="hover:underline"><i class="fas fa-external-link-alt mr-1"></i>View Homepage (stats section)</a></li>
                    <li><a href="index.php" class="hover:underline"><i class="fas fa-home mr-1"></i>Back to Admin Dashboard</a></li>
                </ul>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
