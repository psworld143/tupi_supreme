<?php
require_once 'config.php';
requireLogin();

$db = getDB();

// Auto-create the product_tabs table if it doesn't exist yet
$db->query("CREATE TABLE IF NOT EXISTS `product_tabs` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `tab_key` varchar(50) NOT NULL,
    `label` varchar(100) NOT NULL,
    `icon` varchar(50) DEFAULT 'fa-cube',
    `keywords` varchar(200) DEFAULT '',
    `display_order` int(11) DEFAULT 0,
    `is_active` tinyint(1) DEFAULT 1,
    `is_system` tinyint(1) DEFAULT 0,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    `updated_by` int(11) DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `tab_key` (`tab_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

// Seed the three built-in system tabs if the table is empty
$seed_check = $db->query("SELECT COUNT(*) as cnt FROM product_tabs");
if ($seed_check && (int)$seed_check->fetch_assoc()['cnt'] === 0) {
    $system_tabs = [
        ['granulated', 'Granulated Activated Carbon', 'fa-cubes',   'granulated',     1],
        ['husk',       'Coconut Husk Products',       'fa-seedling', 'husk,coconut',  2],
        ['custom',     'Custom Formulations',         'fa-cogs',     'custom',        3],
    ];
    $stmt = $db->prepare("INSERT INTO product_tabs (tab_key, label, icon, keywords, display_order, is_active, is_system, updated_by) VALUES (?, ?, ?, ?, ?, 1, 1, ?)");
    foreach ($system_tabs as $t) {
        $stmt->bind_param("ssssii", $t[0], $t[1], $t[2], $t[3], $t[4], $_SESSION['admin_id']);
        $stmt->execute();
    }
}

$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? null;
$error = '';
$success = '';

// Status flash from PRG redirect
$status_param = $_GET['status'] ?? '';
if ($status_param === 'saved')   $success = 'Product tab saved successfully!';
if ($status_param === 'deleted') $success = 'Product tab deleted successfully!';

// Handle form submissions (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['delete_id'] ?? '') === '') {
    $tab_key = sanitizeInput($_POST['tab_key'] ?? '');
    $label = sanitizeInput($_POST['label'] ?? '');
    $icon = sanitizeInput($_POST['icon'] ?? 'fa-cube');
    $keywords = sanitizeInput($_POST['keywords'] ?? '');
    $display_order = intval($_POST['display_order'] ?? 0);
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    if (empty($tab_key) || empty($label)) {
        $error = 'Tab key and label are required.';
    } else {
        if ($action === 'add') {
            // Check for duplicate tab_key
            $chk = $db->prepare("SELECT id FROM product_tabs WHERE tab_key = ?");
            $chk->bind_param("s", $tab_key);
            $chk->execute();
            if ($chk->get_result()->num_rows > 0) {
                $error = 'A tab with this key already exists. Choose a unique key.';
            } else {
                $stmt = $db->prepare("INSERT INTO product_tabs (tab_key, label, icon, keywords, display_order, is_active, is_system, updated_by) VALUES (?, ?, ?, ?, ?, ?, 0, ?)");
                $stmt->bind_param("ssssiii", $tab_key, $label, $icon, $keywords, $display_order, $is_active, $_SESSION['admin_id']);
                if ($stmt->execute()) {
                    logActivity('create', 'product_tabs', $db->insert_id, "Added product tab: {$label}");
                    redirect('product-tabs.php?status=saved');
                } else {
                    $error = 'Error adding tab: ' . $stmt->error;
                }
            }
        } elseif ($action === 'edit' && $id) {
            $stmt = $db->prepare("UPDATE product_tabs SET label = ?, icon = ?, keywords = ?, display_order = ?, is_active = ?, updated_by = ? WHERE id = ?");
            $stmt->bind_param("sssiiii", $label, $icon, $keywords, $display_order, $is_active, $_SESSION['admin_id'], $id);
            if ($stmt->execute()) {
                logActivity('update', 'product_tabs', $id, "Updated product tab: {$label}");
                redirect('product-tabs.php?status=saved');
            } else {
                $error = 'Error updating tab: ' . $stmt->error;
            }
        }
    }
}

// Handle delete (POST only — system tabs can't be deleted)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['delete_id'] ?? '') !== '') {
    $del_id = intval($_POST['delete_id']);
    // Verify it's not a system tab
    $chk = $db->prepare("SELECT is_system, label FROM product_tabs WHERE id = ?");
    $chk->bind_param("i", $del_id);
    $chk->execute();
    $row = $chk->get_result()->fetch_assoc();
    if ($row && (int)$row['is_system'] === 1) {
        $error = 'System tabs cannot be deleted. You can deactivate them instead.';
    } else {
        $stmt = $db->prepare("DELETE FROM product_tabs WHERE id = ? AND is_system = 0");
        $stmt->bind_param("i", $del_id);
        if ($stmt->execute()) {
            logActivity('delete', 'product_tabs', $del_id, 'Deleted product tab: ' . ($row['label'] ?? ''));
            redirect('product-tabs.php?status=deleted');
        } else {
            $error = 'Error deleting tab: ' . $stmt->error;
        }
    }
}

// Get tab for edit
$edit_tab = null;
if ($action === 'edit' && $id) {
    $stmt = $db->prepare("SELECT * FROM product_tabs WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $edit_tab = $result->fetch_assoc();
    if (!$edit_tab) {
        $error = 'Product tab not found.';
        $action = 'list';
    }
}

// Get all tabs for list
$all_tabs = [];
if ($action === 'list') {
    $res = $db->query("SELECT pt.*, au.username as updated_by_name FROM product_tabs pt LEFT JOIN admin_users au ON pt.updated_by = au.id ORDER BY pt.display_order, pt.id");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $all_tabs[] = $row;
        }
    }
}

// Quick stats
$stats = ['total' => 0, 'active' => 0, 'inactive' => 0, 'system' => 0];
$st = $db->query("SELECT COUNT(*) total, SUM(is_active) active, SUM(is_system) system_tabs FROM product_tabs");
if ($st) {
    $row = $st->fetch_assoc();
    $stats['total'] = (int)$row['total'];
    $stats['active'] = (int)$row['active'];
    $stats['inactive'] = $stats['total'] - $stats['active'];
    $stats['system'] = (int)$row['system_tabs'];
}

// Common Font Awesome icon options for the datalist
$icon_options = ['fa-cube', 'fa-cubes', 'fa-seedling', 'fa-leaf', 'fa-cogs', 'fa-tools', 'fa-flask', 'fa-industry', 'fa-water', 'fa-tint', 'fa-wind', 'fa-fire', 'fa-box', 'fa-boxes', 'fa-recycle', 'fa-tree'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Tabs Management - <?php echo SITE_NAME; ?></title>
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
                        <i class="fas fa-folder text-primary"></i> Product Tabs Management
                    </h1>
                    <p class="text-sm text-gray-500 mt-1">Manage the tab buttons on the public Products page. Each tab lists products whose name or slug contains the tab's keywords.</p>
                </div>
                <?php if ($action === 'list'): ?>
                <a href="?action=add" class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-secondary transition-colors inline-flex items-center justify-center">
                    <i class="fas fa-plus mr-2"></i>Add New Tab
                </a>
                <?php else: ?>
                <a href="product-tabs.php" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200 transition-colors inline-flex items-center justify-center">
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
                <h2 class="text-2xl font-bold mb-1"><?php echo $action === 'add' ? 'Add New' : 'Edit'; ?> Product Tab</h2>
                <p class="text-sm text-gray-500 mb-4">Fields marked <span class="text-red-500">*</span> are required.</p>

                <!-- Info banner -->
                <div class="mb-6 p-3 rounded-lg bg-blue-50 border border-blue-200 flex items-start gap-2">
                    <i class="fas fa-info-circle text-blue-500 mt-0.5"></i>
                    <p class="text-sm text-blue-800">Tabs are the pill buttons on the public <a href="../products.php#main" target="_blank" class="underline hover:text-blue-900">Products page</a>. A product appears in a tab if its <strong>category</strong> is set to this tab OR its name/slug contains any of the tab's <strong>keywords</strong>. The three built-in tabs (Granulated, Husk, Custom) are system tabs and can't be deleted.</p>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Left: form fields -->
                    <div>
                        <form method="POST" action="">
                            <div class="space-y-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Label <span class="text-red-500">*</span></label>
                                    <input type="text" name="label" id="label_input" required
                                           value="<?php echo htmlspecialchars($edit_tab['label'] ?? ''); ?>"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary"
                                           placeholder="e.g., Water Filter Products"
                                           oninput="updatePreview(); autoSuggestKey()">
                                    <p class="text-xs text-gray-400 mt-1">The text shown on the tab button (visible to visitors).</p>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Tab Key <span class="text-red-500">*</span></label>
                                    <?php if ($action === 'add'): ?>
                                        <input type="text" name="tab_key" id="tab_key_input" required pattern="[a-z0-9\-_]+"
                                               value="<?php echo htmlspecialchars($edit_tab['tab_key'] ?? ''); ?>"
                                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary font-mono"
                                               placeholder="e.g., water-filters">
                                        <p class="text-xs text-gray-400 mt-1">Unique identifier (lowercase, hyphens, numbers). Used internally — not shown to visitors. <button type="button" onclick="autoSuggestKey()" class="text-primary hover:underline">Auto-generate from label</button>.</p>
                                    <?php else: ?>
                                        <input type="text" value="<?php echo htmlspecialchars($edit_tab['tab_key'] ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-100 font-mono" readonly disabled>
                                        <input type="hidden" name="tab_key" value="<?php echo htmlspecialchars($edit_tab['tab_key'] ?? ''); ?>">
                                        <p class="text-xs text-gray-400 mt-1">The tab key can't be changed after creation.</p>
                                    <?php endif; ?>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Icon</label>
                                    <div class="flex items-center gap-3">
                                        <input type="text" name="icon" id="icon_input" list="icon-list"
                                               value="<?php echo htmlspecialchars($edit_tab['icon'] ?? 'fa-cube'); ?>"
                                               class="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary font-mono"
                                               placeholder="fa-cube"
                                               oninput="updateIconPreview(this.value); updatePreview()">
                                        <div id="icon_preview" class="flex items-center justify-center w-10 h-10 border border-gray-300 rounded-lg bg-gray-50 text-lg text-primary">
                                            <i class="fas <?php echo htmlspecialchars($edit_tab['icon'] ?? 'fa-cube'); ?>"></i>
                                        </div>
                                    </div>
                                    <datalist id="icon-list">
                                        <?php foreach ($icon_options as $ic): ?>
                                            <option value="<?php echo htmlspecialchars($ic); ?>">
                                        <?php endforeach; ?>
                                    </datalist>
                                    <p class="text-xs text-gray-400 mt-1">Font Awesome icon class without the <code>fas</code> prefix. <a href="https://fontawesome.com/v6/search?o=r&m=free" target="_blank" class="text-primary hover:underline">Browse icons</a>.</p>
                                    <div class="mt-2 flex flex-wrap gap-1">
                                        <?php foreach (['fa-cube','fa-cubes','fa-seedling','fa-leaf','fa-cogs','fa-tools','fa-flask','fa-industry','fa-water','fa-tint','fa-wind','fa-fire','fa-box','fa-recycle','fa-tree'] as $ic): ?>
                                            <button type="button" class="quick-pick" onclick="setIcon('<?php echo $ic; ?>')"><?php echo $ic; ?></button>
                                        <?php endforeach; ?>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Display Order</label>
                                        <input type="number" name="display_order" min="0"
                                               value="<?php echo htmlspecialchars($edit_tab['display_order'] ?? 0); ?>"
                                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary">
                                        <p class="text-xs text-gray-400 mt-1">Lower numbers appear first (left to right). Use 10, 20, 30…</p>
                                    </div>

                                    <div class="flex items-center">
                                        <label class="flex items-center cursor-pointer select-none">
                                            <input type="checkbox" name="is_active" value="1"
                                                   <?php echo ($edit_tab && $edit_tab['is_active']) || !$edit_tab ? 'checked' : ''; ?>
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
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Keywords <span class="text-red-500">*</span></label>
                                    <input type="text" name="keywords" id="keywords_input" required
                                           value="<?php echo htmlspecialchars($edit_tab['keywords'] ?? ''); ?>"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary"
                                           placeholder="e.g., water,filter,purification"
                                           oninput="updatePreview()">
                                    <p class="text-xs text-gray-400 mt-1">Comma-separated words. A product appears in this tab if its name or slug contains any of these keywords (case-insensitive).</p>
                                    <div class="mt-2 flex flex-wrap gap-1">
                                        <button type="button" class="quick-pick" onclick="addKeyword('water')">+ water</button>
                                        <button type="button" class="quick-pick" onclick="addKeyword('filter')">+ filter</button>
                                        <button type="button" class="quick-pick" onclick="addKeyword('carbon')">+ carbon</button>
                                        <button type="button" class="quick-pick" onclick="addKeyword('coconut')">+ coconut</button>
                                        <button type="button" class="quick-pick" onclick="addKeyword('husk')">+ husk</button>
                                        <button type="button" class="quick-pick" onclick="addKeyword('powder')">+ powder</button>
                                        <button type="button" class="quick-pick" onclick="addKeyword('granulated')">+ granulated</button>
                                        <button type="button" class="quick-pick" onclick="addKeyword('custom')">+ custom</button>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-8 flex flex-col sm:flex-row gap-3">
                                <button type="submit" class="bg-primary text-white px-6 py-2.5 rounded-lg hover:bg-secondary transition-colors inline-flex items-center justify-center">
                                    <i class="fas fa-save mr-2"></i><?php echo $action === 'add' ? 'Add Tab' : 'Save Changes'; ?>
                                </button>
                                <a href="product-tabs.php" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-6 py-2.5 rounded-lg transition-colors inline-flex items-center justify-center">
                                    <i class="fas fa-times mr-2"></i>Cancel
                                </a>
                            </div>
                        </form>
                    </div>

                    <!-- Right: live preview -->
                    <div>
                        <p class="text-sm font-medium text-gray-700 mb-2 flex items-center gap-2">
                            <i class="fas fa-eye text-gray-400"></i> Live Preview
                            <span class="text-xs text-gray-400 font-normal">(how the tab appears on the Products page)</span>
                        </p>

                        <!-- Tab button preview (the pill) -->
                        <div class="border border-gray-200 rounded-lg p-6 bg-gray-50 mb-4">
                            <p class="text-xs text-gray-400 mb-3">Tab button (among other tabs):</p>
                            <div class="flex flex-wrap gap-3">
                                <button class="border-2 border-[#d6ded9] text-[#23332c] px-6 py-3 rounded-full font-medium text-sm bg-white">
                                    <i class="fas fa-cubes mr-2"></i>Granulated
                                </button>
                                <button class="border-2 border-primary text-white px-6 py-3 rounded-full font-medium text-sm bg-primary">
                                    <i id="preview_icon" class="fas <?php echo htmlspecialchars($edit_tab['icon'] ?? 'fa-cube'); ?> mr-2"></i><span id="preview_label"><?php echo htmlspecialchars($edit_tab['label'] ?? 'Tab label'); ?></span>
                                </button>
                                <button class="border-2 border-[#d6ded9] text-[#23332c] px-6 py-3 rounded-full font-medium text-sm bg-white">
                                    <i class="fas fa-cogs mr-2"></i>Custom
                                </button>
                            </div>
                        </div>

                        <!-- Tab pane header preview -->
                        <div class="rounded-lg overflow-hidden border border-gray-200">
                            <div class="bg-[#60796e] text-white p-8 text-center relative overflow-hidden">
                                <div class="absolute inset-0 opacity-25" style="background: #8bc34a; border-radius: 50%; filter: blur(40px); width: 100px; height: 100px; top: -20px; right: -20px;"></div>
                                <div class="relative">
                                    <span class="bg-white/15 text-white/90 mb-4 inline-block px-2 py-0.5 text-xs rounded-full">
                                        <i id="preview_icon2" class="fas <?php echo htmlspecialchars($edit_tab['icon'] ?? 'fa-cube'); ?> text-xs"></i> Products
                                    </span>
                                    <h3 id="preview_label2" class="text-2xl lg:text-3xl font-bold mb-4 mt-3"><?php echo htmlspecialchars($edit_tab['label'] ?? 'Tab label'); ?></h3>
                                </div>
                            </div>
                        </div>

                        <!-- Keywords preview -->
                        <div class="mt-4 p-3 rounded-lg bg-gray-50 border border-gray-200">
                            <p class="text-xs text-gray-400 mb-1">Products matching these keywords will appear here:</p>
                            <div class="flex flex-wrap gap-1" id="preview_keywords">
                                <?php
                                $kws = array_filter(array_map('trim', explode(',', $edit_tab['keywords'] ?? '')));
                                if (!empty($kws)):
                                    foreach ($kws as $kw):
                                ?>
                                    <span class="px-2 py-0.5 text-xs rounded-full bg-primary/10 text-primary font-mono"><?php echo htmlspecialchars($kw); ?></span>
                                <?php
                                    endforeach;
                                else:
                                ?>
                                    <span class="text-xs text-gray-400">No keywords yet — add some above.</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <p class="text-xs text-gray-400 mt-2">The active tab is highlighted in green. The pane header shows the icon and label on a dark green background.</p>
                    </div>
                </div>
            </div>

            <script>
            function setIcon(i) {
                document.getElementById('icon_input').value = i;
                updateIconPreview(i);
                updatePreview();
            }

            function updateIconPreview(iconClass) {
                document.getElementById('icon_preview').innerHTML = '<i class="fas ' + iconClass + '"></i>';
            }

            function updatePreview() {
                var label = document.getElementById('label_input').value || 'Tab label';
                var icon = document.getElementById('icon_input').value || 'fa-cube';
                var keywords = document.getElementById('keywords_input').value || '';

                document.getElementById('preview_label').textContent = label;
                document.getElementById('preview_label2').textContent = label;
                document.getElementById('preview_icon').className = 'fas ' + icon + ' mr-2';
                document.getElementById('preview_icon2').className = 'fas ' + icon + ' text-xs';

                // Keywords preview
                var kws = keywords.split(',').map(function(s){ return s.trim(); }).filter(function(s){ return s.length > 0; });
                var kwBox = document.getElementById('preview_keywords');
                if (kws.length > 0) {
                    kwBox.innerHTML = kws.map(function(k) {
                        return '<span class="px-2 py-0.5 text-xs rounded-full bg-primary/10 text-primary font-mono">' + escapeHtml(k) + '</span>';
                    }).join('');
                } else {
                    kwBox.innerHTML = '<span class="text-xs text-gray-400">No keywords yet — add some above.</span>';
                }
            }

            function addKeyword(kw) {
                var input = document.getElementById('keywords_input');
                var current = input.value.trim();
                if (!current) {
                    input.value = kw;
                } else {
                    var parts = current.split(',').map(function(s){ return s.trim(); }).filter(function(s){ return s.length > 0; });
                    if (parts.indexOf(kw) === -1) {
                        parts.push(kw);
                    }
                    input.value = parts.join(', ');
                }
                updatePreview();
            }

            function autoSuggestKey() {
                var keyInput = document.getElementById('tab_key_input');
                if (!keyInput || keyInput.value.trim() !== '') return; // don't overwrite
                var label = document.getElementById('label_input').value || '';
                var key = label.toLowerCase()
                    .replace(/[^a-z0-9\s-]/g, '')
                    .replace(/[\s_]+/g, '-')
                    .replace(/-+/g, '-')
                    .replace(/^-|-$/g, '');
                keyInput.value = key;
            }

            function escapeHtml(s) {
                return s.replace(/[&<>"']/g, function(c) {
                    return {'&':'&','<':'<','>':'>','"':'"',"'":'&#39;'}[c];
                });
            }
            </script>

        <?php else: ?>
            <!-- List View -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <!-- Stats -->
                <div class="p-4 bg-gray-50 border-b">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="px-3 py-1 text-xs rounded-full bg-gray-200 text-gray-700"><i class="fas fa-layer-group mr-1"></i><?php echo $stats['total']; ?> total</span>
                        <span class="px-3 py-1 text-xs rounded-full bg-green-100 text-green-800"><i class="fas fa-check mr-1"></i><?php echo $stats['active']; ?> active</span>
                        <span class="px-3 py-1 text-xs rounded-full bg-red-100 text-red-800"><i class="fas fa-pause mr-1"></i><?php echo $stats['inactive']; ?> inactive</span>
                        <span class="px-3 py-1 text-xs rounded-full bg-blue-100 text-blue-800"><i class="fas fa-lock mr-1"></i><?php echo $stats['system']; ?> system</span>
                    </div>
                </div>

                <div class="overflow-auto" style="max-height: 55vh;">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50 sticky top-0">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Icon</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Label</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Keywords</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (empty($all_tabs)): ?>
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center">
                                    <i class="fas fa-folder text-4xl text-gray-300 mb-3"></i>
                                    <p class="text-gray-500 mb-2">No product tabs found.</p>
                                    <a href="?action=add" class="text-primary hover:underline text-sm"><i class="fas fa-plus mr-1"></i>Add new tab</a>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($all_tabs as $tab): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($tab['display_order']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <i class="fas <?php echo htmlspecialchars($tab['icon']); ?> text-lg text-primary"></i>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($tab['label']); ?></div>
                                        <div class="text-xs text-gray-400"><?php echo htmlspecialchars($tab['tab_key']); ?></div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex flex-wrap gap-1">
                                            <?php foreach (array_filter(explode(',', $tab['keywords'])) as $kw): ?>
                                                <span class="px-2 py-0.5 text-xs rounded-full bg-gray-100 text-gray-600"><?php echo htmlspecialchars(trim($kw)); ?></span>
                                            <?php endforeach; ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php if ((int)$tab['is_system'] === 1): ?>
                                            <span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800"><i class="fas fa-lock mr-1"></i>System</span>
                                        <?php else: ?>
                                            <span class="px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-600">Custom</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php if ($tab['is_active']): ?>
                                            <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800"><i class="fas fa-check mr-1"></i>Active</span>
                                        <?php else: ?>
                                            <span class="px-2 py-1 text-xs rounded-full bg-red-100 text-red-800"><i class="fas fa-pause mr-1"></i>Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <a href="?action=edit&id=<?php echo $tab['id']; ?>" class="text-primary hover:text-secondary mr-3" title="Edit"><i class="fas fa-edit"></i></a>
                                        <?php if ((int)$tab['is_system'] !== 1): ?>
                                            <form method="POST" action="" class="inline" onsubmit="return confirm('Delete this tab? This cannot be undone.');">
                                                <input type="hidden" name="delete_id" value="<?php echo $tab['id']; ?>">
                                                <button type="submit" class="text-red-600 hover:text-red-800" title="Delete"><i class="fas fa-trash"></i></button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
                </div>

                <div class="p-4 bg-gray-50 border-t text-sm text-gray-500">
                    <p><i class="fas fa-info-circle mr-1"></i> <strong>System tabs</strong> (the original 3) can be edited but not deleted. <strong>Custom tabs</strong> you add list products whose name/slug matches the tab's keywords. Manage products in the Products section.</p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
