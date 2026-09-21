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
if ($status_param === 'saved')   $success = 'Contact info saved successfully!';
if ($status_param === 'deleted') $success = 'Contact info entry deleted successfully!';

$valid_types = ['address', 'phone', 'email'];

// Handle form submissions (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['delete_id'] ?? '') === '') {
    if (!verifyCsrfToken()) {
        $error = 'Security token expired or invalid. Please reload the page and try again.';
    } else {
        $type = sanitizeInput($_POST['type'] ?? '');
        $label = sanitizeInput($_POST['label'] ?? '');
        $value = trim($_POST['value'] ?? '');
        $icon = sanitizeInput($_POST['icon'] ?? '');
        $display_order = intval($_POST['display_order'] ?? 0);
        $is_active = isset($_POST['is_active']) ? 1 : 0;

        if (!in_array($type, $valid_types, true)) {
            $error = 'Please choose a valid type.';
        } elseif (empty($value)) {
            $error = 'Value is required.';
        } else {
            if ($action === 'add') {
                $stmt = $db->prepare("INSERT INTO contact_info (type, label, value, icon, display_order, is_active, updated_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssssiii", $type, $label, $value, $icon, $display_order, $is_active, $_SESSION['admin_id']);
                if ($stmt->execute()) {
                    logActivity('create', 'contact_info', $db->insert_id, "Added contact info ({$type}): {$label}");
                    redirect('contact-info.php?status=saved');
                } else {
                    $error = 'Error: ' . $stmt->error;
                }
            } elseif ($action === 'edit' && $id) {
                $stmt = $db->prepare("UPDATE contact_info SET type = ?, label = ?, value = ?, icon = ?, display_order = ?, is_active = ?, updated_by = ? WHERE id = ?");
                $stmt->bind_param("ssssiiii", $type, $label, $value, $icon, $display_order, $is_active, $_SESSION['admin_id'], $id);
                if ($stmt->execute()) {
                    logActivity('update', 'contact_info', $id, "Updated contact info ({$type}): {$label}");
                    redirect('contact-info.php?status=saved');
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
        $stmt = $db->prepare("DELETE FROM contact_info WHERE id = ?");
        $stmt->bind_param("i", $del_id);
        if ($stmt->execute()) {
            logActivity('delete', 'contact_info', $del_id, 'Deleted contact info entry');
            redirect('contact-info.php?status=deleted');
        } else {
            $error = 'Error deleting contact info: ' . $stmt->error;
        }
    }
}

// Get entry for edit
$edit_info = null;
if ($action === 'edit' && $id) {
    $stmt = $db->prepare("SELECT * FROM contact_info WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $edit_info = $result->fetch_assoc();
    if (!$edit_info) {
        $error = 'Contact info entry not found.';
        $action = 'list';
    }
}

// Get all entries for list (with pagination + filtering + search)
$all_info = [];
$total_info = 0;
$total_pages = 1;
$current_page_num = 1;
$per_page = 10;

if ($action === 'list') {
    $current_page_num = max(1, intval($_GET['page'] ?? 1));
    $filter_status = $_GET['filter_status'] ?? '';
    $filter_type = $_GET['filter_type'] ?? '';
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
    if (in_array($filter_type, $valid_types, true)) {
        $where[] = 'type = ?';
        $params[] = $filter_type;
        $types .= 's';
    }
    if ($search_q !== '') {
        $where[] = '(label LIKE ? OR value LIKE ?)';
        $like = '%' . $search_q . '%';
        $params[] = $like;
        $params[] = $like;
        $types .= 'ss';
    }
    $where_sql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

    // Total count
    $count_stmt = $db->prepare("SELECT COUNT(*) as total FROM contact_info $where_sql");
    if ($types && $count_stmt) {
        $count_stmt->bind_param($types, ...$params);
    }
    if ($count_stmt) {
        $count_stmt->execute();
        $total_info = $count_stmt->get_result()->fetch_assoc()['total'];
    }
    $total_pages = max(1, ceil($total_info / $per_page));
    if ($current_page_num > $total_pages) {
        $current_page_num = $total_pages;
    }
    $offset = ($current_page_num - 1) * $per_page;

    // List query
    $list_stmt = $db->prepare("SELECT * FROM contact_info $where_sql ORDER BY type, display_order, id LIMIT ? OFFSET ?");
    $list_params = $params;
    $list_types = $types . 'ii';
    $list_params[] = $per_page;
    $list_params[] = $offset;
    if ($list_stmt) {
        $list_stmt->bind_param($list_types, ...$list_params);
        $list_stmt->execute();
        $result = $list_stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $all_info[] = $row;
        }
    }
}

// Quick stats for the list header
$stats = ['total' => 0, 'active' => 0, 'inactive' => 0, 'address' => 0, 'phone' => 0, 'email' => 0];
$st = $db->query("SELECT COUNT(*) total, SUM(is_active) active, SUM(type='address') address, SUM(type='phone') phone, SUM(type='email') email FROM contact_info");
if ($st) {
    $row = $st->fetch_assoc();
    $stats['total'] = (int)$row['total'];
    $stats['active'] = (int)$row['active'];
    $stats['inactive'] = $stats['total'] - $stats['active'];
    $stats['address'] = (int)$row['address'];
    $stats['phone'] = (int)$row['phone'];
    $stats['email'] = (int)$row['email'];
}

// Contact info renders as cards at the top of the public Contact page
$view_url = '../contact.php#contact-section';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Info - <?php echo SITE_NAME; ?></title>
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
                        <i class="fas fa-address-book text-primary"></i> Contact Info
                    </h1>
                    <p class="text-sm text-gray-500 mt-1">Manage the address, phone, and email cards on the Contact page.</p>
                </div>
                <?php if ($action === 'list'): ?>
                <a href="?action=add" class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-secondary transition-colors inline-flex items-center justify-center">
                    <i class="fas fa-plus mr-2"></i>Add New Entry
                </a>
                <?php else: ?>
                <a href="contact-info.php" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200 transition-colors inline-flex items-center justify-center">
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
                <h2 class="text-2xl font-bold mb-1"><?php echo $action === 'add' ? 'Add New' : 'Edit'; ?> Contact Info</h2>
                <p class="text-sm text-gray-500 mb-4">Fields marked <span class="text-red-500">*</span> are required.</p>

                <!-- Info banner -->
                <div class="mb-6 p-3 rounded-lg bg-blue-50 border border-blue-200 flex items-start gap-2">
                    <i class="fas fa-info-circle text-blue-500 mt-0.5"></i>
                    <div class="text-sm text-blue-800">
                        <p>How these render on the public <a href="<?php echo $view_url; ?>" target="_blank" class="underline hover:text-blue-900">Contact page</a>:</p>
                        <ul class="list-disc ml-5 mt-1 space-y-0.5">
                            <li><strong>Address</strong> entries each get their own card (icon + label + multi-line value).</li>
                            <li><strong>Phone</strong> entries are grouped into one "Call Us" card as "Label: number" lines.</li>
                            <li><strong>Email</strong> entries are grouped into one "Email Us" card as "Label: address" lines.</li>
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
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Type <span class="text-red-500">*</span></label>
                                        <select name="type" id="type_input" required
                                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary"
                                                onchange="updatePreview()">
                                            <?php foreach ($valid_types as $t): ?>
                                                <option value="<?php echo $t; ?>" <?php echo (($edit_info['type'] ?? 'address') === $t) ? 'selected' : ''; ?>><?php echo ucfirst($t); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <p class="text-xs text-gray-400 mt-1">Controls how and where the entry appears.</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Label</label>
                                        <input type="text" name="label" id="label_input" maxlength="100"
                                               value="<?php echo htmlspecialchars($edit_info['label'] ?? ''); ?>"
                                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary"
                                               placeholder="e.g., Visit Us / Main / Sales"
                                               oninput="updatePreview()">
                                        <p class="text-xs text-gray-400 mt-1">Address: card heading. Phone/Email: the line's label.</p>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Value <span class="text-red-500">*</span></label>
                                    <textarea name="value" id="value_input" rows="3" required
                                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary"
                                              placeholder="e.g., +1 (555) 123-4567"
                                              oninput="updatePreview()"><?php echo htmlspecialchars($edit_info['value'] ?? ''); ?></textarea>
                                    <p class="text-xs text-gray-400 mt-1">Phone number, email address, or address text. Line breaks are preserved (useful for addresses).</p>
                                </div>

                                <div id="icon-field">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Icon <span class="text-gray-400 font-normal">(Font Awesome class — address cards only)</span></label>
                                    <input type="text" name="icon" id="icon_input" maxlength="100"
                                           value="<?php echo htmlspecialchars($edit_info['icon'] ?? ''); ?>"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary font-mono text-sm"
                                           placeholder="e.g., fa-map-marker-alt"
                                           oninput="updatePreview()">
                                    <p class="text-xs text-gray-400 mt-1">Only used on address cards — phone/email cards use fixed icons. Defaults to a map pin.</p>
                                    <div class="mt-2 flex flex-wrap gap-1">
                                        <button type="button" class="quick-pick" onclick="setIcon('fa-map-marker-alt')">map pin</button>
                                        <button type="button" class="quick-pick" onclick="setIcon('fa-building')">building</button>
                                        <button type="button" class="quick-pick" onclick="setIcon('fa-warehouse')">warehouse</button>
                                        <button type="button" class="quick-pick" onclick="setIcon('fa-industry')">industry</button>
                                        <button type="button" class="quick-pick" onclick="setIcon('fa-globe')">globe</button>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Display Order</label>
                                        <input type="number" name="display_order" min="0"
                                               value="<?php echo htmlspecialchars($edit_info['display_order'] ?? 0); ?>"
                                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary">
                                        <p class="text-xs text-gray-400 mt-1">Lower numbers appear first within each type.</p>
                                    </div>
                                    <div class="flex items-center">
                                        <label class="flex items-center cursor-pointer select-none">
                                            <input type="checkbox" name="is_active" value="1"
                                                   <?php echo ($edit_info && $edit_info['is_active']) || !$edit_info ? 'checked' : ''; ?>
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
                                    <i class="fas fa-save mr-2"></i><?php echo $action === 'add' ? 'Add Entry' : 'Save Changes'; ?>
                                </button>
                                <a href="contact-info.php" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-6 py-2.5 rounded-lg transition-colors inline-flex items-center justify-center">
                                    <i class="fas fa-times mr-2"></i>Cancel
                                </a>
                            </div>
                        </form>
                    </div>

                    <!-- Right: live preview -->
                    <div>
                        <p class="text-sm font-medium text-gray-700 mb-2 flex items-center gap-2">
                            <i class="fas fa-eye text-gray-400"></i> Live Preview
                            <span class="text-xs text-gray-400 font-normal">(contact card)</span>
                        </p>
                        <div class="bg-white border border-[#e6ece8] rounded-2xl p-8 text-center max-w-sm">
                            <div class="w-16 h-16 rounded-2xl flex items-center justify-center mx-auto mb-6" style="background: linear-gradient(135deg, #3d7a66, #60796e);">
                                <i id="preview_icon" class="fas fa-map-marker-alt text-2xl text-white"></i>
                            </div>
                            <h4 id="preview_heading" class="text-lg font-semibold text-[#23332c] mb-3">Visit Us</h4>
                            <p id="preview_value" class="text-[#7d8b84] leading-relaxed text-sm"><?php echo nl2br(htmlspecialchars($edit_info['value'] ?? 'Value')); ?></p>
                        </div>
                        <p class="text-xs text-gray-400 mt-2">Address entries preview as a full card; phone/email entries preview as a line inside their shared card.</p>
                    </div>
                </div>
            </div>

            <script>
            var typeDefaults = {
                address: { heading: 'label',  fallback: 'Visit Us',  defaultIcon: 'fa-map-marker-alt' },
                phone:   { heading: 'fixed',  fixed: 'Call Us',      defaultIcon: 'fa-phone' },
                email:   { heading: 'fixed',  fixed: 'Email Us',     defaultIcon: 'fa-envelope' }
            };

            // Quick-pick helper
            function setIcon(cls) {
                document.getElementById('icon_input').value = cls;
                updatePreview();
            }

            // Live preview — mirrors contact.php's per-type rendering
            function updatePreview() {
                var type = document.getElementById('type_input').value;
                var label = document.getElementById('label_input').value || '';
                var value = document.getElementById('value_input').value || 'Value';
                var icon = document.getElementById('icon_input').value || '';
                var def = typeDefaults[type] || typeDefaults.address;

                // Heading: address uses the label, phone/email use fixed card titles
                var heading = def.heading === 'label' ? (label || def.fallback) : def.fixed;
                document.getElementById('preview_heading').textContent = heading;

                // Icon: address uses the field, phone/email use fixed icons
                var iconCls = (type === 'address' && icon.trim()) ? icon : def.defaultIcon;
                document.getElementById('preview_icon').className = 'fas ' + iconCls.replace(/^fas\s+/, '') + ' text-2xl text-white';

                // Value: phone/email render as "Label: value"
                var valueEl = document.getElementById('preview_value');
                if (type === 'address') {
                    valueEl.innerText = value;
                } else {
                    valueEl.innerText = (label || (type === 'phone' ? 'Phone' : 'Email')) + ': ' + value;
                }

                // Icon field is only relevant for addresses
                document.getElementById('icon-field').style.opacity = (type === 'address') ? '1' : '0.45';
            }

            updatePreview();
            </script>

        <?php else: ?>
            <!-- List View -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <!-- Stats + Filters -->
                <div class="p-4 bg-gray-50 border-b">
                    <div class="flex flex-wrap items-center gap-2 mb-4">
                        <span class="px-3 py-1 text-xs rounded-full bg-gray-200 text-gray-700"><i class="fas fa-layer-group mr-1"></i><?php echo $stats['total']; ?> total</span>
                        <span class="px-3 py-1 text-xs rounded-full bg-blue-100 text-blue-800"><i class="fas fa-map-marker-alt mr-1"></i><?php echo $stats['address']; ?> addresses</span>
                        <span class="px-3 py-1 text-xs rounded-full bg-blue-100 text-blue-800"><i class="fas fa-phone mr-1"></i><?php echo $stats['phone']; ?> phones</span>
                        <span class="px-3 py-1 text-xs rounded-full bg-blue-100 text-blue-800"><i class="fas fa-envelope mr-1"></i><?php echo $stats['email']; ?> emails</span>
                        <span class="px-3 py-1 text-xs rounded-full bg-red-100 text-red-800"><i class="fas fa-pause mr-1"></i><?php echo $stats['inactive']; ?> inactive</span>
                    </div>
                    <form method="GET" action="contact-info.php" class="flex flex-col sm:flex-row gap-2">
                        <div class="flex-1 flex flex-col sm:flex-row gap-2">
                            <select name="filter_type" class="px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-primary focus:border-primary">
                                <option value="">All types</option>
                                <?php foreach ($valid_types as $t): ?>
                                    <option value="<?php echo $t; ?>" <?php echo (($_GET['filter_type'] ?? '') === $t) ? 'selected' : ''; ?>><?php echo ucfirst($t); ?></option>
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
                                       placeholder="Search labels or values…"
                                       class="w-full pl-9 pr-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-primary focus:border-primary">
                            </div>
                        </div>
                        <button type="submit" class="bg-primary text-white px-4 py-2 rounded-md hover:bg-secondary transition-colors text-sm inline-flex items-center justify-center">
                            <i class="fas fa-filter mr-1"></i>Filter
                        </button>
                        <?php if (!empty($_GET['filter_type']) || !empty($_GET['filter_status']) || !empty($_GET['q'])): ?>
                        <a href="contact-info.php" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-200 transition-colors text-sm inline-flex items-center justify-center">
                            <i class="fas fa-times mr-1"></i>Clear
                        </a>
                        <?php endif; ?>
                    </form>
                </div>

                <div class="overflow-auto" style="max-height: 55vh;">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50 sticky top-0">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Label</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Value</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (empty($all_info)): ?>
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center">
                                    <i class="fas fa-address-book text-4xl text-gray-300 mb-3"></i>
                                    <p class="text-gray-500 mb-2">No contact info entries found.</p>
                                    <a href="?action=add" class="text-primary hover:underline text-sm"><i class="fas fa-plus mr-1"></i>Add new entry</a>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($all_info as $info):
                                $type_icons = ['address' => 'fa-map-marker-alt', 'phone' => 'fa-phone', 'email' => 'fa-envelope'];
                            ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 text-sm">
                                        <span class="inline-flex items-center gap-2 px-2 py-1 rounded-full bg-gray-100 text-gray-700 text-xs">
                                            <i class="fas <?php echo $type_icons[$info['type']] ?? 'fa-tag'; ?>"></i><?php echo ucfirst(htmlspecialchars($info['type'])); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm font-medium text-gray-900"><?php echo htmlspecialchars($info['label'] ?: '—'); ?></td>
                                    <td class="px-6 py-4 text-sm text-gray-500 max-w-xs truncate" title="<?php echo htmlspecialchars($info['value']); ?>"><?php echo htmlspecialchars(mb_strimwidth($info['value'], 0, 60, '…')); ?></td>
                                    <td class="px-6 py-4 text-sm text-gray-900"><?php echo (int)$info['display_order']; ?></td>
                                    <td class="px-6 py-4">
                                        <?php if ($info['is_active']): ?>
                                            <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">Active</span>
                                        <?php else: ?>
                                            <span class="px-2 py-1 text-xs rounded-full bg-red-100 text-red-800">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 text-sm font-medium whitespace-nowrap">
                                        <a href="?action=edit&id=<?php echo $info['id']; ?>" class="text-primary hover:text-secondary mr-3" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="<?php echo $view_url; ?>" target="_blank" class="text-gray-500 hover:text-gray-700 mr-3" title="View on site">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <form method="POST" action="contact-info.php" class="inline" onsubmit="return confirm('Delete this contact info entry? This cannot be undone.');">
                                            <?php echo csrfTokenField(); ?>
                                            <input type="hidden" name="delete_id" value="<?php echo $info['id']; ?>">
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
                    'total_items'   => $total_info,
                    'per_page'      => $per_page,
                    'base_query'    => $_GET,
                ]);
                ?>
            </div>

            <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
                <h3 class="font-semibold text-blue-900 mb-2">Quick Links</h3>
                <ul class="text-sm text-blue-800 space-y-1">
                    <li><a href="<?php echo $view_url; ?>" target="_blank" class="hover:underline"><i class="fas fa-external-link-alt mr-1"></i>View Contact Page</a></li>
                    <li><a href="office-hours.php" class="hover:underline"><i class="fas fa-clock mr-1"></i>Manage Office Hours</a></li>
                    <li><a href="subject-options.php" class="hover:underline"><i class="fas fa-list-ul mr-1"></i>Manage Subject Options</a></li>
                    <li><a href="index.php" class="hover:underline"><i class="fas fa-home mr-1"></i>Back to Admin Dashboard</a></li>
                </ul>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
