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
if ($status_param === 'saved')   $success = 'Company value saved successfully!';
if ($status_param === 'deleted') $success = 'Company value deleted successfully!';

// Handle form submissions (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['delete_id'] ?? '') === '') {
    if (!verifyCsrfToken()) {
        $error = 'Security token expired or invalid. Please reload the page and try again.';
    } else {
        $title = sanitizeInput($_POST['title'] ?? '');
        $description = $_POST['description'] ?? '';
        $icon = sanitizeInput($_POST['icon'] ?? '');
        $display_order = intval($_POST['display_order'] ?? 0);
        $is_active = isset($_POST['is_active']) ? 1 : 0;

        if (empty($title)) {
            $error = 'Title is required.';
        } else {
            if ($action === 'add') {
                $stmt = $db->prepare("INSERT INTO company_values (title, description, icon, display_order, is_active, updated_by) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sssiii", $title, $description, $icon, $display_order, $is_active, $_SESSION['admin_id']);
                if ($stmt->execute()) {
                    logActivity('create', 'company_values', $db->insert_id, "Added company value: {$title}");
                    redirect('company-values.php?status=saved');
                } else {
                    $error = 'Error: ' . $stmt->error;
                }
            } elseif ($action === 'edit' && $id) {
                $stmt = $db->prepare("UPDATE company_values SET title = ?, description = ?, icon = ?, display_order = ?, is_active = ?, updated_by = ? WHERE id = ?");
                $stmt->bind_param("sssiiii", $title, $description, $icon, $display_order, $is_active, $_SESSION['admin_id'], $id);
                if ($stmt->execute()) {
                    logActivity('update', 'company_values', $id, "Updated company value: {$title}");
                    redirect('company-values.php?status=saved');
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
        $stmt = $db->prepare("DELETE FROM company_values WHERE id = ?");
        $stmt->bind_param("i", $del_id);
        if ($stmt->execute()) {
            logActivity('delete', 'company_values', $del_id, 'Deleted company value');
            redirect('company-values.php?status=deleted');
        } else {
            $error = 'Error deleting company value: ' . $stmt->error;
        }
    }
}

// Get value for edit
$edit_value = null;
if ($action === 'edit' && $id) {
    $stmt = $db->prepare("SELECT * FROM company_values WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $edit_value = $result->fetch_assoc();
    if (!$edit_value) {
        $error = 'Company value not found.';
        $action = 'list';
    }
}

// Get all values for list (with pagination + filtering + search)
$all_values = [];
$total_values = 0;
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
        $where[] = '(title LIKE ? OR description LIKE ?)';
        $like = '%' . $search_q . '%';
        $params[] = $like;
        $params[] = $like;
        $types .= 'ss';
    }
    $where_sql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

    // Total count
    $count_stmt = $db->prepare("SELECT COUNT(*) as total FROM company_values $where_sql");
    if ($types && $count_stmt) {
        $count_stmt->bind_param($types, ...$params);
    }
    if ($count_stmt) {
        $count_stmt->execute();
        $total_values = $count_stmt->get_result()->fetch_assoc()['total'];
    }
    $total_pages = max(1, ceil($total_values / $per_page));
    if ($current_page_num > $total_pages) {
        $current_page_num = $total_pages;
    }
    $offset = ($current_page_num - 1) * $per_page;

    // List query
    $list_stmt = $db->prepare("SELECT * FROM company_values $where_sql ORDER BY display_order, id LIMIT ? OFFSET ?");
    $list_params = $params;
    $list_types = $types . 'ii';
    $list_params[] = $per_page;
    $list_params[] = $offset;
    if ($list_stmt) {
        $list_stmt->bind_param($list_types, ...$list_params);
        $list_stmt->execute();
        $result = $list_stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $all_values[] = $row;
        }
    }
}

// Quick stats for the list header
$stats = ['total' => 0, 'active' => 0, 'inactive' => 0];
$st = $db->query("SELECT COUNT(*) total, SUM(is_active) active FROM company_values");
if ($st) {
    $row = $st->fetch_assoc();
    $stats['total'] = (int)$row['total'];
    $stats['active'] = (int)$row['active'];
    $stats['inactive'] = $stats['total'] - $stats['active'];
}

// Company values render in the Core Values section on the public About page
$view_url = '../about.php#values';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Company Values - <?php echo SITE_NAME; ?></title>
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
                        <i class="fas fa-gem text-primary"></i> Company Values
                    </h1>
                    <p class="text-sm text-gray-500 mt-1">Manage the core values cards shown on the About page.</p>
                </div>
                <?php if ($action === 'list'): ?>
                <a href="?action=add" class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-secondary transition-colors inline-flex items-center justify-center">
                    <i class="fas fa-plus mr-2"></i>Add New Value
                </a>
                <?php else: ?>
                <a href="company-values.php" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200 transition-colors inline-flex items-center justify-center">
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
                <h2 class="text-2xl font-bold mb-1"><?php echo $action === 'add' ? 'Add New' : 'Edit'; ?> Company Value</h2>
                <p class="text-sm text-gray-500 mb-4">Fields marked <span class="text-red-500">*</span> are required.</p>

                <!-- Info banner -->
                <div class="mb-6 p-3 rounded-lg bg-blue-50 border border-blue-200 flex items-start gap-2">
                    <i class="fas fa-info-circle text-blue-500 mt-0.5"></i>
                    <p class="text-sm text-blue-800">Company values appear as cards in the dark "Core Values" section of the public <a href="<?php echo $view_url; ?>" target="_blank" class="underline hover:text-blue-900">About page</a> — a gradient icon tile, a title, and a short description. The section heading is editable under <a href="about.php" class="underline hover:text-blue-900">About Page</a> (values_title / values_subtitle).</p>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Left: form fields -->
                    <div>
                        <form method="POST" action="">
                            <?php echo csrfTokenField(); ?>
                            <div class="space-y-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Title <span class="text-red-500">*</span></label>
                                    <input type="text" name="title" id="title_input" required maxlength="200"
                                           value="<?php echo htmlspecialchars($edit_value['title'] ?? ''); ?>"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary"
                                           placeholder="e.g., Quality First"
                                           oninput="updatePreview()">
                                    <p class="text-xs text-gray-400 mt-1">The value's heading on the card.</p>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                                    <textarea name="description" id="description_input" rows="4"
                                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary"
                                              placeholder="e.g., We never compromise on quality. Every product meets the highest industry standards."
                                              oninput="updatePreview()"><?php echo htmlspecialchars($edit_value['description'] ?? ''); ?></textarea>
                                    <p class="text-xs text-gray-400 mt-1">Short supporting text under the title.</p>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Icon <span class="text-gray-400 font-normal">(Font Awesome class)</span></label>
                                    <input type="text" name="icon" id="icon_input" maxlength="100"
                                           value="<?php echo htmlspecialchars($edit_value['icon'] ?? ''); ?>"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary font-mono text-sm"
                                           placeholder="e.g., fas fa-check-circle"
                                           oninput="updatePreview()">
                                    <p class="text-xs text-gray-400 mt-1">Any Font Awesome 6 class. Leave blank to hide the icon tile.</p>
                                    <div class="mt-2 flex flex-wrap gap-1">
                                        <button type="button" class="quick-pick" onclick="setIcon('fas fa-check-circle')">check</button>
                                        <button type="button" class="quick-pick" onclick="setIcon('fas fa-leaf')">leaf</button>
                                        <button type="button" class="quick-pick" onclick="setIcon('fas fa-users')">users</button>
                                        <button type="button" class="quick-pick" onclick="setIcon('fas fa-lightbulb')">lightbulb</button>
                                        <button type="button" class="quick-pick" onclick="setIcon('fas fa-award')">award</button>
                                        <button type="button" class="quick-pick" onclick="setIcon('fas fa-handshake')">handshake</button>
                                        <button type="button" class="quick-pick" onclick="setIcon('fas fa-globe')">globe</button>
                                        <button type="button" class="quick-pick" onclick="setIcon('fas fa-shield-alt')">shield</button>
                                        <button type="button" class="quick-pick" onclick="setIcon('fas fa-heart')">heart</button>
                                        <button type="button" class="quick-pick" onclick="setIcon('fas fa-star')">star</button>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Display Order</label>
                                        <input type="number" name="display_order" min="0"
                                               value="<?php echo htmlspecialchars($edit_value['display_order'] ?? 0); ?>"
                                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary">
                                        <p class="text-xs text-gray-400 mt-1">Lower numbers appear first.</p>
                                    </div>
                                    <div class="flex items-center">
                                        <label class="flex items-center cursor-pointer select-none">
                                            <input type="checkbox" name="is_active" value="1"
                                                   <?php echo ($edit_value && $edit_value['is_active']) || !$edit_value ? 'checked' : ''; ?>
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
                                    <i class="fas fa-save mr-2"></i><?php echo $action === 'add' ? 'Add Value' : 'Save Changes'; ?>
                                </button>
                                <a href="company-values.php" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-6 py-2.5 rounded-lg transition-colors inline-flex items-center justify-center">
                                    <i class="fas fa-times mr-2"></i>Cancel
                                </a>
                            </div>
                        </form>
                    </div>

                    <!-- Right: live preview -->
                    <div>
                        <p class="text-sm font-medium text-gray-700 mb-2 flex items-center gap-2">
                            <i class="fas fa-eye text-gray-400"></i> Live Preview
                            <span class="text-xs text-gray-400 font-normal">(value card)</span>
                        </p>
                        <div class="rounded-2xl p-6" style="background: #23332c;">
                            <div class="rounded-2xl p-8 text-center border border-white/10" style="background: rgba(255,255,255,0.05);">
                                <div id="preview_icon_wrap" class="w-16 h-16 rounded-2xl flex items-center justify-center mx-auto mb-6 <?php echo empty($edit_value['icon'] ?? '') ? 'hidden' : ''; ?>" style="background: linear-gradient(135deg, #3d7a66, #60796e);">
                                    <i id="preview_icon" class="<?php echo htmlspecialchars($edit_value['icon'] ?? 'fas fa-check-circle'); ?> text-2xl text-white"></i>
                                </div>
                                <h4 id="preview_title" class="text-xl font-semibold mb-3 text-white"><?php echo htmlspecialchars($edit_value['title'] ?? 'Value title'); ?></h4>
                                <p id="preview_desc" class="leading-relaxed text-sm <?php echo empty($edit_value['description'] ?? '') ? 'hidden' : ''; ?>" style="color: rgba(255,255,255,0.7);"><?php echo htmlspecialchars($edit_value['description'] ?? ''); ?></p>
                            </div>
                        </div>
                        <p class="text-xs text-gray-400 mt-2">The card style mirrors the value cards on the public About page.</p>
                    </div>
                </div>
            </div>

            <script>
            // Quick-pick helper
            function setIcon(cls) {
                document.getElementById('icon_input').value = cls;
                updatePreview();
            }

            // Live preview
            function updatePreview() {
                var title = document.getElementById('title_input').value || 'Value title';
                var desc = document.getElementById('description_input').value || '';
                var icon = document.getElementById('icon_input').value || '';

                document.getElementById('preview_title').textContent = title;

                var descEl = document.getElementById('preview_desc');
                if (desc.trim()) { descEl.textContent = desc; descEl.classList.remove('hidden'); }
                else { descEl.classList.add('hidden'); }

                var iconWrap = document.getElementById('preview_icon_wrap');
                var iconEl = document.getElementById('preview_icon');
                if (icon.trim()) {
                    iconEl.className = icon + ' text-2xl text-white';
                    iconWrap.classList.remove('hidden');
                } else {
                    iconWrap.classList.add('hidden');
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
                    <form method="GET" action="company-values.php" class="flex flex-col sm:flex-row gap-2">
                        <div class="flex-1 flex flex-col sm:flex-row gap-2">
                            <select name="filter_status" class="px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-primary focus:border-primary">
                                <option value="">All statuses</option>
                                <option value="active" <?php echo (($_GET['filter_status'] ?? '') === 'active') ? 'selected' : ''; ?>>Active only</option>
                                <option value="inactive" <?php echo (($_GET['filter_status'] ?? '') === 'inactive') ? 'selected' : ''; ?>>Inactive only</option>
                            </select>
                            <div class="relative flex-1">
                                <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                                <input type="text" name="q" value="<?php echo htmlspecialchars($_GET['q'] ?? ''); ?>"
                                       placeholder="Search titles or descriptions…"
                                       class="w-full pl-9 pr-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-primary focus:border-primary">
                            </div>
                        </div>
                        <button type="submit" class="bg-primary text-white px-4 py-2 rounded-md hover:bg-secondary transition-colors text-sm inline-flex items-center justify-center">
                            <i class="fas fa-filter mr-1"></i>Filter
                        </button>
                        <?php if (!empty($_GET['filter_status']) || !empty($_GET['q'])): ?>
                        <a href="company-values.php" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-200 transition-colors text-sm inline-flex items-center justify-center">
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
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Icon</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (empty($all_values)): ?>
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center">
                                    <i class="fas fa-gem text-4xl text-gray-300 mb-3"></i>
                                    <p class="text-gray-500 mb-2">No company values found.</p>
                                    <a href="?action=add" class="text-primary hover:underline text-sm"><i class="fas fa-plus mr-1"></i>Add new value</a>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($all_values as $value): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 text-sm">
                                        <p class="font-medium text-gray-900"><?php echo htmlspecialchars($value['title']); ?></p>
                                        <?php if ($value['description']): ?>
                                            <p class="text-xs text-gray-400 mt-1"><?php echo htmlspecialchars(mb_strimwidth($value['description'], 0, 90, '…')); ?></p>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 text-sm">
                                        <?php if ($value['icon']): ?>
                                            <span class="inline-flex items-center gap-2">
                                                <span class="w-8 h-8 rounded-lg flex items-center justify-center" style="background: linear-gradient(135deg, #3d7a66, #60796e);">
                                                    <i class="<?php echo htmlspecialchars($value['icon']); ?> text-white text-sm"></i>
                                                </span>
                                                <code class="text-xs text-gray-400"><?php echo htmlspecialchars($value['icon']); ?></code>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-gray-400">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900"><?php echo (int)$value['display_order']; ?></td>
                                    <td class="px-6 py-4">
                                        <?php if ($value['is_active']): ?>
                                            <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">Active</span>
                                        <?php else: ?>
                                            <span class="px-2 py-1 text-xs rounded-full bg-red-100 text-red-800">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 text-sm font-medium whitespace-nowrap">
                                        <a href="?action=edit&id=<?php echo $value['id']; ?>" class="text-primary hover:text-secondary mr-3" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="<?php echo $view_url; ?>" target="_blank" class="text-gray-500 hover:text-gray-700 mr-3" title="View on site">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <form method="POST" action="company-values.php" class="inline" onsubmit="return confirm('Delete this company value? This cannot be undone.');">
                                            <?php echo csrfTokenField(); ?>
                                            <input type="hidden" name="delete_id" value="<?php echo $value['id']; ?>">
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
                    'total_items'   => $total_values,
                    'per_page'      => $per_page,
                    'base_query'    => $_GET,
                ]);
                ?>
            </div>

            <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
                <h3 class="font-semibold text-blue-900 mb-2">Quick Links</h3>
                <ul class="text-sm text-blue-800 space-y-1">
                    <li><a href="<?php echo $view_url; ?>" target="_blank" class="hover:underline"><i class="fas fa-external-link-alt mr-1"></i>View Values on About Page</a></li>
                    <li><a href="about.php" class="hover:underline"><i class="fas fa-info-circle mr-1"></i>Edit About Page Sections</a></li>
                    <li><a href="index.php" class="hover:underline"><i class="fas fa-home mr-1"></i>Back to Admin Dashboard</a></li>
                </ul>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
