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
if ($status_param === 'saved')   $success = 'Certification saved successfully!';
if ($status_param === 'deleted') $success = 'Certification deleted successfully!';

// Handle form submissions (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['delete_id'] ?? '') === '') {
    $title = sanitizeInput($_POST['title'] ?? '');
    $category = in_array($_POST['category'] ?? '', ['iso', 'product']) ? $_POST['category'] : 'iso';
    $subtitle = sanitizeInput($_POST['subtitle'] ?? '');
    $icon = sanitizeInput($_POST['icon'] ?? '');
    $issuing_organization = sanitizeInput($_POST['issuing_organization'] ?? '');
    $certificate_number = sanitizeInput($_POST['certificate_number'] ?? '');
    $issue_date = sanitizeInput($_POST['issue_date'] ?? null);
    $expiry_date = sanitizeInput($_POST['expiry_date'] ?? null);
    $description = $_POST['description'] ?? '';
    $features = sanitizeInput($_POST['features'] ?? '');
    $badge_label = sanitizeInput($_POST['badge_label'] ?? '');
    $image_url = sanitizeInput($_POST['image_url'] ?? '');
    $document_url = sanitizeInput($_POST['document_url'] ?? '');
    $display_order = intval($_POST['display_order'] ?? 0);
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    if (empty($title)) {
        $error = 'Title is required.';
    } else {
        if ($action === 'add') {
            $stmt = $db->prepare("INSERT INTO certifications (title, category, subtitle, icon, issuing_organization, certificate_number, issue_date, expiry_date, description, features, badge_label, image_url, document_url, display_order, is_active, updated_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssssssssssiii", $title, $category, $subtitle, $icon, $issuing_organization, $certificate_number, $issue_date, $expiry_date, $description, $features, $badge_label, $image_url, $document_url, $display_order, $is_active, $_SESSION['admin_id']);
            if ($stmt->execute()) {
                logActivity('create', 'certifications', $db->insert_id, "Added certification: {$title}");
                redirect('certifications.php?status=saved');
            } else {
                $error = 'Error: ' . $stmt->error;
            }
        } elseif ($action === 'edit' && $id) {
            $stmt = $db->prepare("UPDATE certifications SET title = ?, category = ?, subtitle = ?, icon = ?, issuing_organization = ?, certificate_number = ?, issue_date = ?, expiry_date = ?, description = ?, features = ?, badge_label = ?, image_url = ?, document_url = ?, display_order = ?, is_active = ?, updated_by = ? WHERE id = ?");
            $stmt->bind_param("sssssssssssssiiii", $title, $category, $subtitle, $icon, $issuing_organization, $certificate_number, $issue_date, $expiry_date, $description, $features, $badge_label, $image_url, $document_url, $display_order, $is_active, $_SESSION['admin_id'], $id);
            if ($stmt->execute()) {
                logActivity('update', 'certifications', $id, "Updated certification: {$title}");
                redirect('certifications.php?status=saved');
            } else {
                $error = 'Error: ' . $stmt->error;
            }
        }
    }
}

// Handle delete (POST only — safer than GET)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['delete_id'] ?? '') !== '') {
    $del_id = intval($_POST['delete_id']);
    $stmt = $db->prepare("DELETE FROM certifications WHERE id = ?");
    $stmt->bind_param("i", $del_id);
    if ($stmt->execute()) {
        logActivity('delete', 'certifications', $del_id, 'Deleted certification');
        redirect('certifications.php?status=deleted');
    } else {
        $error = 'Error deleting certification: ' . $stmt->error;
    }
}

// Get certification for edit
$edit_cert = null;
if ($action === 'edit' && $id) {
    $stmt = $db->prepare("SELECT * FROM certifications WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $edit_cert = $result->fetch_assoc();
    if (!$edit_cert) {
        $error = 'Certification not found.';
        $action = 'list';
    }
}

// Get all certifications for list (with pagination + filtering + search)
$all_certs = [];
$total_certs = 0;
$total_pages = 1;
$current_page_num = 1;
$per_page = 5;

if ($action === 'list') {
    $per_page = 5;
    $current_page_num = max(1, intval($_GET['page'] ?? 1));
    $filter_status = $_GET['filter_status'] ?? '';
    $filter_expiry = $_GET['filter_expiry'] ?? '';
    $filter_category = $_GET['filter_category'] ?? '';
    $search_q = trim($_GET['q'] ?? '');

    // Build dynamic WHERE
    $where = [];
    $params = [];
    $types = '';
    if ($filter_category === 'iso' || $filter_category === 'product') {
        $where[] = 'category = ?';
        $params[] = $filter_category;
        $types .= 's';
    }
    if ($filter_status === 'active') {
        $where[] = 'is_active = 1';
    } elseif ($filter_status === 'inactive') {
        $where[] = 'is_active = 0';
    }
    if ($filter_expiry === 'valid') {
        $where[] = '(expiry_date IS NULL OR expiry_date = "0000-00-00" OR expiry_date >= CURDATE())';
    } elseif ($filter_expiry === 'expired') {
        $where[] = '(expiry_date IS NOT NULL AND expiry_date > "0000-00-00" AND expiry_date < CURDATE())';
    }
    if ($search_q !== '') {
        $where[] = '(title LIKE ? OR issuing_organization LIKE ? OR certificate_number LIKE ?)';
        $like = '%' . $search_q . '%';
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
        $types .= 'sss';
    }
    $where_sql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

    // Total count
    $count_sql = "SELECT COUNT(*) as total FROM certifications $where_sql";
    $count_stmt = $db->prepare($count_sql);
    if ($types && $count_stmt) {
        $count_stmt->bind_param($types, ...$params);
    }
    if ($count_stmt) {
        $count_stmt->execute();
        $total_certs = $count_stmt->get_result()->fetch_assoc()['total'];
    }
    $total_pages = max(1, ceil($total_certs / $per_page));
    if ($current_page_num > $total_pages) {
        $current_page_num = $total_pages;
    }
    $offset = ($current_page_num - 1) * $per_page;

    // List query
    $list_sql = "SELECT * FROM certifications $where_sql ORDER BY category, display_order, title LIMIT ? OFFSET ?";
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
            $all_certs[] = $row;
        }
    }
}

// Quick stats for the list header
$stats = ['total' => 0, 'active' => 0, 'inactive' => 0, 'expired' => 0];
$st = $db->query("SELECT COUNT(*) total, SUM(is_active) active, SUM(CASE WHEN expiry_date IS NOT NULL AND expiry_date > '0000-00-00' AND expiry_date < CURDATE() THEN 1 ELSE 0 END) expired FROM certifications");
if ($st) {
    $row = $st->fetch_assoc();
    $stats['total'] = (int)$row['total'];
    $stats['active'] = (int)$row['active'];
    $stats['inactive'] = $stats['total'] - $stats['active'];
    $stats['expired'] = (int)$row['expired'];
}

// Certifications render in the ISO / Product Certifications sections on the public page
$view_url = '../certifications.php#iso-certifications';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/png" href="../uploads/images/favicon.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certifications Management - <?php echo SITE_NAME; ?></title>
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
                        <i class="fas fa-certificate text-primary"></i> Certifications Management
                    </h1>
                    <p class="text-sm text-gray-500 mt-1">Manage ISO certifications, quality standards, and compliance documents.</p>
                </div>
                <?php if ($action === 'list'): ?>
                <a href="?action=add" class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-secondary transition-colors inline-flex items-center justify-center">
                    <i class="fas fa-plus mr-2"></i>Add New Certification
                </a>
                <?php else: ?>
                <a href="certifications.php" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200 transition-colors inline-flex items-center justify-center">
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
                <h2 class="text-2xl font-bold mb-1"><?php echo $action === 'add' ? 'Add New' : 'Edit'; ?> Certification</h2>
                <p class="text-sm text-gray-500 mb-4">Fields marked <span class="text-red-500">*</span> are required.</p>

                <!-- Info banner -->
                <div class="mb-6 p-3 rounded-lg bg-blue-50 border border-blue-200 flex items-start gap-2">
                    <i class="fas fa-info-circle text-blue-500 mt-0.5"></i>
                    <p class="text-sm text-blue-800">Certifications are stored in the database and managed here. They appear as cards on the public <a href="<?php echo $view_url; ?>" target="_blank" class="underline hover:text-blue-900">Certifications page</a> — each card shows a badge/icon, title, issuing organization, description, and validity dates. Tip: use the <strong>Image</strong> field for a badge/logo image, and the <strong>Document</strong> field for a downloadable certificate PDF.</p>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Left: form fields -->
                    <div>
                        <form method="POST" action="">
                            <?php echo csrfTokenField(); ?>
                            <div class="space-y-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Title <span class="text-red-500">*</span></label>
                                    <input type="text" name="title" id="title_input" required
                                           value="<?php echo htmlspecialchars($edit_cert['title'] ?? ''); ?>"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary"
                                           placeholder="e.g., ISO 9001:2015 Quality Management"
                                           oninput="updatePreview()">
                                    <p class="text-xs text-gray-400 mt-1">The certification name shown on the card.</p>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Card Section <span class="text-red-500">*</span></label>
                                        <select name="category" id="category_input" required
                                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary"
                                                onchange="updatePreview()">
                                            <option value="iso" <?php echo ($edit_cert['category'] ?? 'iso') === 'iso' ? 'selected' : ''; ?>>ISO Certifications (left-icon card + checklist)</option>
                                            <option value="product" <?php echo ($edit_cert['category'] ?? '') === 'product' ? 'selected' : ''; ?>>Product Certifications (centered card + badge)</option>
                                        </select>
                                        <p class="text-xs text-gray-400 mt-1">Which section of the Certifications page this card appears in.</p>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Icon <span class="text-gray-400 font-normal">(optional)</span></label>
                                        <div class="flex items-center gap-3">
                                            <input type="text" name="icon" id="icon_input"
                                                   value="<?php echo htmlspecialchars($edit_cert['icon'] ?? ''); ?>"
                                                   class="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary font-mono"
                                                   placeholder="fas fa-certificate"
                                                   oninput="updatePreview()">
                                            <div id="icon_preview" class="flex items-center justify-center w-10 h-10 border border-gray-300 rounded-lg bg-gray-50 text-lg text-primary">
                                                <i class="<?php echo htmlspecialchars($edit_cert['icon'] ?? '') ?: 'fas fa-certificate'; ?>"></i>
                                            </div>
                                        </div>
                                        <p class="text-xs text-gray-400 mt-1">Font Awesome class for the card badge. <a href="https://fontawesome.com/v6/search?o=r&m=free" target="_blank" class="text-primary hover:underline">Browse icons</a>.</p>
                                        <div class="mt-2 flex flex-wrap gap-1">
                                            <button type="button" class="quick-pick" onclick="setIcon('fas fa-certificate')">certificate</button>
                                            <button type="button" class="quick-pick" onclick="setIcon('fas fa-shield-alt')">shield-alt</button>
                                            <button type="button" class="quick-pick" onclick="setIcon('fas fa-check-circle')">check-circle</button>
                                            <button type="button" class="quick-pick" onclick="setIcon('fas fa-award')">award</button>
                                            <button type="button" class="quick-pick" onclick="setIcon('fas fa-flask')">flask</button>
                                            <button type="button" class="quick-pick" onclick="setIcon('fas fa-leaf')">leaf</button>
                                        </div>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Subtitle <span class="text-gray-400 font-normal">(ISO cards)</span></label>
                                        <input type="text" name="subtitle" id="subtitle_input"
                                               value="<?php echo htmlspecialchars($edit_cert['subtitle'] ?? ''); ?>"
                                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary"
                                               placeholder="e.g., Quality Management Systems"
                                               oninput="updatePreview()">
                                        <p class="text-xs text-gray-400 mt-1">Green line under the title on ISO-style cards.</p>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Badge Label <span class="text-gray-400 font-normal">(product cards)</span></label>
                                        <input type="text" name="badge_label" id="badge_label_input"
                                               value="<?php echo htmlspecialchars($edit_cert['badge_label'] ?? ''); ?>"
                                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary"
                                               placeholder="e.g., Compliant"
                                               oninput="updatePreview()">
                                        <p class="text-xs text-gray-400 mt-1">Small pill shown at the bottom of product-certification cards.</p>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Checklist Items <span class="text-gray-400 font-normal">(ISO cards, optional)</span></label>
                                    <textarea name="features" id="features_input" rows="3"
                                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary"
                                              placeholder="Item one • Item two • Item three"
                                              oninput="updatePreview()"><?php echo htmlspecialchars($edit_cert['features'] ?? ''); ?></textarea>
                                    <p class="text-xs text-gray-400 mt-1">Bullet list shown under the description on ISO-style cards. Separate items with <code class="bg-gray-100 px-1 rounded">•</code>.</p>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Issuing Organization</label>
                                        <input type="text" name="issuing_organization" id="org_input"
                                               value="<?php echo htmlspecialchars($edit_cert['issuing_organization'] ?? ''); ?>"
                                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary"
                                               placeholder="e.g., International Organization for Standardization"
                                               oninput="updatePreview()">
                                        <p class="text-xs text-gray-400 mt-1">The body that issued the certification (optional).</p>
                                        <div class="mt-2 flex flex-wrap gap-1">
                                            <button type="button" class="quick-pick" onclick="setOrg('International Organization for Standardization')">ISO</button>
                                            <button type="button" class="quick-pick" onclick="setOrg('NSF International')">NSF</button>
                                            <button type="button" class="quick-pick" onclick="setOrg('TÜV SÜD')">TÜV SÜD</button>
                                            <button type="button" class="quick-pick" onclick="setOrg('SGS')">SGS</button>
                                            <button type="button" class="quick-pick" onclick="setOrg('Bureau Veritas')">Bureau Veritas</button>
                                        </div>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Certificate Number</label>
                                        <input type="text" name="certificate_number" id="cert_num_input"
                                               value="<?php echo htmlspecialchars($edit_cert['certificate_number'] ?? ''); ?>"
                                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary"
                                               placeholder="e.g., CERT-2024-001"
                                               oninput="updatePreview()">
                                        <p class="text-xs text-gray-400 mt-1">The official certificate number (optional).</p>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Issue Date</label>
                                        <input type="date" name="issue_date" id="issue_date_input"
                                               value="<?php echo htmlspecialchars($edit_cert['issue_date'] ?? ''); ?>"
                                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary"
                                               onchange="updatePreview()">
                                        <p class="text-xs text-gray-400 mt-1">When the certification was issued (optional).</p>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Expiry Date</label>
                                        <input type="date" name="expiry_date" id="expiry_date_input"
                                               value="<?php echo htmlspecialchars($edit_cert['expiry_date'] ?? ''); ?>"
                                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary"
                                               onchange="updatePreview()">
                                        <p class="text-xs text-gray-400 mt-1">Leave blank if the certification does not expire.</p>
                                    </div>
                                </div>

                                <!-- Image (badge/logo) -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Badge Image <span class="text-gray-400 font-normal">(optional)</span></label>
                                    <div class="mb-3">
                                        <label class="block text-xs font-medium text-gray-500 mb-1">Image source</label>
                                        <select id="image-source-mode" onchange="switchImageMode()" class="w-full max-w-xs px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary text-sm">
                                            <option value="upload">Upload from file</option>
                                            <option value="url">Enter image URL</option>
                                        </select>
                                    </div>

                                    <div id="image-preview-container" class="mb-3 <?php echo empty($edit_cert['image_url'] ?? '') ? 'hidden' : ''; ?>">
                                        <img id="image-preview" src="<?php echo htmlspecialchars($edit_cert['image_url'] ?? ''); ?>" alt="Preview" class="max-w-full h-32 object-contain border border-gray-300 rounded-lg p-2 bg-gray-50">
                                        <button type="button" onclick="clearImagePreview()" class="mt-2 text-sm text-red-600 hover:text-red-800"><i class="fas fa-times mr-1"></i>Remove Image</button>
                                    </div>

                                    <div id="file-picker-block" class="border-2 border-dashed border-gray-300 rounded-lg p-4 mb-3">
                                        <div class="text-center">
                                            <input type="file" id="image-file-input" accept="image/jpeg,image/png,image/gif,image/webp" class="hidden">
                                            <label for="image-file-input" class="cursor-pointer inline-flex items-center px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg transition-colors">
                                                <i class="fas fa-upload mr-2"></i>Choose Image File
                                            </label>
                                            <p class="mt-2 text-xs text-gray-500">JPEG, PNG, GIF, or WebP (Max 10MB). Recommended: square badge/logo images.</p>
                                        </div>
                                        <div id="upload-progress" class="hidden mt-2">
                                            <div class="bg-gray-200 rounded-full h-2">
                                                <div id="upload-progress-bar" class="bg-primary h-2 rounded-full transition-all" style="width: 0%"></div>
                                            </div>
                                            <p id="upload-status" class="text-sm text-gray-600 mt-1"></p>
                                        </div>
                                    </div>

                                    <div id="url-input-block" class="hidden mb-3">
                                        <input type="url" id="image-url-visible" value="<?php echo htmlspecialchars($edit_cert['image_url'] ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary" placeholder="https://example.com/cert-badge.png" oninput="syncImageUrl(this.value)">
                                        <p class="mt-1 text-xs text-gray-500">Paste a full image URL (https://...).</p>
                                    </div>

                                    <input type="hidden" name="image_url" id="image-url-input" value="<?php echo htmlspecialchars($edit_cert['image_url'] ?? ''); ?>">
                                </div>

                                <!-- Document (certificate PDF) -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Certificate Document <span class="text-gray-400 font-normal">(optional)</span></label>
                                    <div class="mb-3">
                                        <label class="block text-xs font-medium text-gray-500 mb-1">Document source</label>
                                        <select id="doc-source-mode" onchange="switchDocMode()" class="w-full max-w-xs px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary text-sm">
                                            <option value="upload">Upload from file</option>
                                            <option value="url">Enter document URL</option>
                                        </select>
                                    </div>

                                    <div id="doc-picker-block" class="border-2 border-dashed border-gray-300 rounded-lg p-4 mb-3">
                                        <div class="text-center">
                                            <input type="file" id="doc-file-input" accept="application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,.pdf,.doc,.docx" class="hidden">
                                            <label for="doc-file-input" class="cursor-pointer inline-flex items-center px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg transition-colors">
                                                <i class="fas fa-upload mr-2"></i>Choose Document File
                                            </label>
                                            <p class="mt-2 text-xs text-gray-500">PDF, DOC, or DOCX (Max 10MB) — uploads automatically when selected</p>
                                        </div>
                                        <div id="doc-upload-progress" class="hidden mt-2">
                                            <div class="bg-gray-200 rounded-full h-2">
                                                <div id="doc-upload-progress-bar" class="bg-primary h-2 rounded-full transition-all" style="width: 0%"></div>
                                            </div>
                                            <p id="doc-upload-status" class="text-sm text-gray-600 mt-1"></p>
                                        </div>
                                    </div>

                                    <div id="doc-url-block" class="hidden mb-3">
                                        <input type="url" id="doc-url-visible" value="<?php echo htmlspecialchars($edit_cert['document_url'] ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary" placeholder="https://example.com/certificate.pdf" oninput="syncDocUrl(this.value)">
                                        <p class="mt-1 text-xs text-gray-500">Direct link to the certificate document.</p>
                                    </div>

                                    <input type="hidden" name="document_url" id="doc-url-input" value="<?php echo htmlspecialchars($edit_cert['document_url'] ?? ''); ?>">
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Display Order</label>
                                        <input type="number" name="display_order" min="0"
                                               value="<?php echo htmlspecialchars($edit_cert['display_order'] ?? 0); ?>"
                                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary">
                                        <p class="text-xs text-gray-400 mt-1">Lower numbers appear first. Use 10, 20, 30…</p>
                                    </div>
                                    <div class="flex items-center">
                                        <label class="flex items-center cursor-pointer select-none">
                                            <input type="checkbox" name="is_active" value="1"
                                                   <?php echo ($edit_cert && $edit_cert['is_active']) || !$edit_cert ? 'checked' : ''; ?>
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
                                              placeholder="e.g., Certified since 2010, demonstrating our commitment to consistent quality management and continuous improvement."
                                              oninput="updatePreview()"><?php echo htmlspecialchars($edit_cert['description'] ?? ''); ?></textarea>
                                    <p class="text-xs text-gray-400 mt-1">Short description of what the certification covers.</p>
                                </div>
                            </div>

                            <div class="mt-8 flex flex-col sm:flex-row gap-3">
                                <button type="submit" class="bg-primary text-white px-6 py-2.5 rounded-lg hover:bg-secondary transition-colors inline-flex items-center justify-center">
                                    <i class="fas fa-save mr-2"></i><?php echo $action === 'add' ? 'Add Certification' : 'Save Changes'; ?>
                                </button>
                                <a href="certifications.php" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-6 py-2.5 rounded-lg transition-colors inline-flex items-center justify-center">
                                    <i class="fas fa-times mr-2"></i>Cancel
                                </a>
                            </div>
                        </form>
                    </div>

                    <!-- Right: live preview -->
                    <div>
                        <p class="text-sm font-medium text-gray-700 mb-2 flex items-center gap-2">
                            <i class="fas fa-eye text-gray-400"></i> Live Preview
                            <span class="text-xs text-gray-400 font-normal">(certification card)</span>
                        </p>
                        <div class="cert-card bg-white border border-[#e6ece8] rounded-2xl p-8" id="preview_card">
                            <div class="flex items-start" id="preview_layout">
                                <div class="cert-badge w-16 h-16 rounded-2xl flex items-center justify-center mr-5 flex-shrink-0 overflow-hidden" style="background: #3d7a66;" id="preview_badge_wrap">
                                    <img id="preview_badge_img" src="<?php echo htmlspecialchars($edit_cert['image_url'] ?? ''); ?>" alt="" class="w-full h-full object-cover <?php echo empty($edit_cert['image_url'] ?? '') ? 'hidden' : ''; ?>">
                                    <i id="preview_badge_icon" class="<?php echo htmlspecialchars($edit_cert['icon'] ?? '') ?: 'fas fa-certificate'; ?> text-2xl text-white <?php echo empty($edit_cert['image_url'] ?? '') ? '' : 'hidden'; ?>"></i>
                                </div>
                                <div class="flex-1" id="preview_body">
                                    <h3 id="preview_title" class="text-xl font-semibold text-[#23332c] mb-1"><?php echo htmlspecialchars($edit_cert['title'] ?? 'Certification title'); ?></h3>
                                    <p id="preview_subtitle" class="text-[#3d7a66] text-sm font-medium mb-3 <?php echo empty($edit_cert['subtitle'] ?? '') ? 'hidden' : ''; ?>"><?php echo htmlspecialchars($edit_cert['subtitle'] ?? ''); ?></p>
                                    <p id="preview_desc" class="text-[#5a6b62] mb-4 leading-relaxed text-sm <?php echo empty($edit_cert['description'] ?? '') ? 'hidden' : ''; ?>"><?php echo htmlspecialchars($edit_cert['description'] ?? ''); ?></p>
                                    <ul id="preview_features" class="text-sm text-[#7d8b84] space-y-1.5 <?php echo empty($edit_cert['features'] ?? '') ? 'hidden' : ''; ?>"></ul>
                                    <div id="preview_badge_label_wrap" class="<?php echo empty($edit_cert['badge_label'] ?? '') ? 'hidden' : ''; ?>">
                                        <span id="preview_badge_label" class="inline-block text-xs font-semibold bg-[#eef3f0] text-[#3d7a66] px-2.5 py-1 rounded-full"><?php echo htmlspecialchars($edit_cert['badge_label'] ?? ''); ?></span>
                                    </div>
                                    <div id="preview_dates" class="text-sm text-[#7d8b84] space-y-1 <?php echo empty($edit_cert['issue_date'] ?? '') && empty($edit_cert['expiry_date'] ?? '') ? 'hidden' : ''; ?>">
                                        <p id="preview_issue" class="flex items-center <?php echo empty($edit_cert['issue_date'] ?? '') ? 'hidden' : ''; ?>"><i class="fas fa-calendar-check text-[#3d7a66] mr-2 text-xs"></i>Issued: <span class="ml-1" id="preview_issue_text"></span></p>
                                        <p id="preview_expiry" class="flex items-center <?php echo empty($edit_cert['expiry_date'] ?? '') ? 'hidden' : ''; ?>"><i class="fas fa-calendar-times text-[#3d7a66] mr-2 text-xs"></i>Valid until: <span class="ml-1" id="preview_expiry_text"></span></p>
                                    </div>
                                    <p id="preview_cert_num" class="text-xs text-[#8a978f] mt-3 <?php echo empty($edit_cert['certificate_number'] ?? '') ? 'hidden' : ''; ?>">Certificate #: <span id="preview_cert_num_text"><?php echo htmlspecialchars($edit_cert['certificate_number'] ?? ''); ?></span></p>
                                </div>
                            </div>
                        </div>
                        <p class="text-xs text-gray-400 mt-2">Preview mirrors the card on the public Certifications page — the layout follows the selected <strong>Card Section</strong>. If a badge image is uploaded, it replaces the icon.</p>
                    </div>
                </div>
            </div>

            <script>
            // Quick-pick helpers
            function setOrg(name) {
                document.getElementById('org_input').value = name;
                updatePreview();
            }
            function setIcon(cls) {
                document.getElementById('icon_input').value = cls;
                updatePreview();
            }

            // Live preview
            function updatePreview() {
                var title = document.getElementById('title_input').value || 'Certification title';
                var category = document.getElementById('category_input').value || 'iso';
                var subtitle = document.getElementById('subtitle_input').value || '';
                var iconCls = document.getElementById('icon_input').value.trim() || 'fas fa-certificate';
                var badgeLabel = document.getElementById('badge_label_input').value || '';
                var features = document.getElementById('features_input').value || '';
                var desc = document.getElementById('description_input').value || '';
                var certNum = document.getElementById('cert_num_input').value || '';
                var issueDate = document.getElementById('issue_date_input').value || '';
                var expiryDate = document.getElementById('expiry_date_input').value || '';
                var imgUrl = document.getElementById('image-url-input').value;

                document.getElementById('preview_title').textContent = title;
                document.getElementById('icon_preview').innerHTML = '<i class="' + iconCls + '"></i>';

                // Card layout follows the category: iso = left icon row, product = centered
                var isProduct = (category === 'product');
                document.getElementById('preview_card').className = 'cert-card bg-white border border-[#e6ece8] rounded-2xl p-8' + (isProduct ? ' text-center' : '');
                document.getElementById('preview_layout').className = isProduct ? '' : 'flex items-start';
                document.getElementById('preview_badge_wrap').className = 'cert-badge rounded-2xl flex items-center justify-center flex-shrink-0 overflow-hidden ' + (isProduct ? 'w-20 h-20 mx-auto mb-6' : 'w-16 h-16 mr-5');

                var subtitleEl = document.getElementById('preview_subtitle');
                if (subtitle.trim()) { subtitleEl.textContent = subtitle; subtitleEl.classList.remove('hidden'); }
                else { subtitleEl.classList.add('hidden'); }

                var descEl = document.getElementById('preview_desc');
                if (desc.trim()) { descEl.textContent = desc; descEl.classList.remove('hidden'); }
                else { descEl.classList.add('hidden'); }

                // Checklist items (•-separated, ISO cards)
                var featEl = document.getElementById('preview_features');
                var items = features.split('•').map(function(s){ return s.trim(); }).filter(Boolean);
                if (items.length) {
                    featEl.innerHTML = items.map(function(s){
                        var li = document.createElement('li');
                        li.className = 'flex items-center' + (isProduct ? ' justify-center' : '');
                        var i = document.createElement('i');
                        i.className = 'fas fa-check text-[#3d7a66] mr-2 text-xs';
                        li.appendChild(i);
                        li.appendChild(document.createTextNode(s));
                        return li.outerHTML;
                    }).join('');
                    featEl.classList.remove('hidden');
                } else {
                    featEl.classList.add('hidden');
                }

                // Badge pill (product cards)
                var blWrap = document.getElementById('preview_badge_label_wrap');
                if (badgeLabel.trim()) {
                    document.getElementById('preview_badge_label').textContent = badgeLabel;
                    blWrap.classList.remove('hidden');
                } else {
                    blWrap.classList.add('hidden');
                }

                // Dates
                var datesEl = document.getElementById('preview_dates');
                var issueEl = document.getElementById('preview_issue');
                var expiryEl = document.getElementById('preview_expiry');
                var hasIssue = issueDate.trim() !== '';
                var hasExpiry = expiryDate.trim() !== '';
                datesEl.classList.toggle('hidden', !hasIssue && !hasExpiry);
                issueEl.classList.toggle('hidden', !hasIssue);
                expiryEl.classList.toggle('hidden', !hasExpiry);
                if (hasIssue) document.getElementById('preview_issue_text').textContent = formatDate(issueDate);
                if (hasExpiry) document.getElementById('preview_expiry_text').textContent = formatDate(expiryDate);

                // Cert number
                var cnEl = document.getElementById('preview_cert_num');
                if (certNum.trim()) {
                    document.getElementById('preview_cert_num_text').textContent = certNum;
                    cnEl.classList.remove('hidden');
                } else {
                    cnEl.classList.add('hidden');
                }

                // Badge image vs icon
                var badgeImg = document.getElementById('preview_badge_img');
                var badgeIcon = document.getElementById('preview_badge_icon');
                badgeIcon.className = iconCls + ' ' + (isProduct ? 'text-3xl' : 'text-2xl') + ' text-white' + (imgUrl.trim() ? ' hidden' : '');
                if (imgUrl.trim()) {
                    badgeImg.src = imgUrl;
                    badgeImg.classList.remove('hidden');
                    badgeIcon.classList.add('hidden');
                } else {
                    badgeImg.classList.add('hidden');
                    badgeIcon.classList.remove('hidden');
                }
            }

            function formatDate(d) {
                if (!d) return '';
                var parts = d.split('-');
                if (parts.length !== 3) return d;
                var months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
                return months[parseInt(parts[1])-1] + ' ' + parts[2] + ', ' + parts[0];
            }
            </script>

            <script>
            // ===== Image upload (badge image) =====
            let selectedImageFile = null;
            let imageUploadState = 'idle';
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

            // ===== Document upload (certificate PDF) =====
            let selectedDocFile = null;
            let docUploadState = 'idle';
            let docMode = 'upload';

            function switchDocMode() {
                const mode = document.getElementById('doc-source-mode').value;
                docMode = mode;
                const fileBlock = document.getElementById('doc-picker-block');
                const urlBlock = document.getElementById('doc-url-block');
                if (mode === 'url') {
                    fileBlock.classList.add('hidden');
                    urlBlock.classList.remove('hidden');
                    document.getElementById('doc-url-visible').value = document.getElementById('doc-url-input').value;
                } else {
                    urlBlock.classList.add('hidden');
                    fileBlock.classList.remove('hidden');
                    if (docUploadState === 'failed') {
                        docUploadState = 'idle';
                        document.getElementById('doc-upload-progress').classList.add('hidden');
                    }
                }
            }

            function syncDocUrl(value) {
                document.getElementById('doc-url-input').value = value;
            }

            document.getElementById('doc-file-input').addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    selectedDocFile = file;
                    docUploadState = 'idle';
                    uploadDocFile();
                }
            });

            function uploadDocFile() {
                if (!selectedDocFile) { alert('Please select a document file first'); return; }
                const formData = new FormData();
                formData.append('document', selectedDocFile);
                const pc = document.getElementById('doc-upload-progress');
                const pb = document.getElementById('doc-upload-progress-bar');
                const st = document.getElementById('doc-upload-status');
                const fl = document.querySelector('label[for="doc-file-input"]');
                docUploadState = 'uploading';
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
                            document.getElementById('doc-url-input').value = r.url;
                            st.textContent = 'Upload successful!'; st.classList.add('text-green-600');
                            docUploadState = 'success';
                            setTimeout(() => { pc.classList.add('hidden'); }, 2000);
                        } else {
                            st.textContent = 'Upload failed: ' + r.error; st.classList.add('text-red-600'); docUploadState = 'failed';
                        }
                    } else {
                        let m = 'Server error'; try { m = JSON.parse(xhr.responseText).error || m; } catch (_) {}
                        st.textContent = 'Upload failed: ' + m; st.classList.add('text-red-600'); docUploadState = 'failed';
                    }
                    fl.style.pointerEvents = ''; fl.style.opacity = '';
                });
                xhr.addEventListener('error', function() {
                    st.textContent = 'Upload failed: Network error'; st.classList.add('text-red-600'); docUploadState = 'failed';
                    fl.style.pointerEvents = ''; fl.style.opacity = '';
                });
                xhr.open('POST', 'api/upload_document.php');
                xhr.send(formData);
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
                        <?php if ($stats['expired'] > 0): ?>
                        <span class="px-3 py-1 text-xs rounded-full bg-orange-100 text-orange-800"><i class="fas fa-exclamation-triangle mr-1"></i><?php echo $stats['expired']; ?> expired</span>
                        <?php endif; ?>
                    </div>
                    <form method="GET" action="certifications.php" class="flex flex-col sm:flex-row gap-2">
                        <div class="flex-1 flex flex-col sm:flex-row gap-2">
                            <select name="filter_status" class="px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-primary focus:border-primary">
                                <option value="">All statuses</option>
                                <option value="active" <?php echo (($_GET['filter_status'] ?? '') === 'active') ? 'selected' : ''; ?>>Active only</option>
                                <option value="inactive" <?php echo (($_GET['filter_status'] ?? '') === 'inactive') ? 'selected' : ''; ?>>Inactive only</option>
                            </select>
                            <select name="filter_category" class="px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-primary focus:border-primary">
                                <option value="">All sections</option>
                                <option value="iso" <?php echo (($_GET['filter_category'] ?? '') === 'iso') ? 'selected' : ''; ?>>ISO Certifications</option>
                                <option value="product" <?php echo (($_GET['filter_category'] ?? '') === 'product') ? 'selected' : ''; ?>>Product Certifications</option>
                            </select>
                            <select name="filter_expiry" class="px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-primary focus:border-primary">
                                <option value="">All dates</option>
                                <option value="valid" <?php echo (($_GET['filter_expiry'] ?? '') === 'valid') ? 'selected' : ''; ?>>Valid only</option>
                                <option value="expired" <?php echo (($_GET['filter_expiry'] ?? '') === 'expired') ? 'selected' : ''; ?>>Expired only</option>
                            </select>
                            <div class="relative flex-1">
                                <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                                <input type="text" name="q" value="<?php echo htmlspecialchars($_GET['q'] ?? ''); ?>"
                                       placeholder="Search title, organization, or cert number…"
                                       class="w-full pl-9 pr-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-primary focus:border-primary">
                            </div>
                        </div>
                        <button type="submit" class="bg-primary text-white px-4 py-2 rounded-md hover:bg-secondary transition-colors text-sm inline-flex items-center justify-center">
                            <i class="fas fa-filter mr-1"></i>Filter
                        </button>
                        <?php if (!empty($_GET['filter_status']) || !empty($_GET['filter_expiry']) || !empty($_GET['filter_category']) || !empty($_GET['q'])): ?>
                        <a href="certifications.php" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-200 transition-colors text-sm inline-flex items-center justify-center">
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
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Section</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Organization</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Issue Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Expiry Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (empty($all_certs)): ?>
                            <tr>
                                <td colspan="8" class="px-6 py-12 text-center">
                                    <i class="fas fa-certificate text-4xl text-gray-300 mb-3"></i>
                                    <p class="text-gray-500 mb-2">No certifications found.</p>
                                    <a href="?action=add" class="text-primary hover:underline text-sm"><i class="fas fa-plus mr-1"></i>Add new certification</a>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($all_certs as $cert):
                                $is_expired = !empty($cert['expiry_date']) && $cert['expiry_date'] !== '0000-00-00' && $cert['expiry_date'] < date('Y-m-d');
                            ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 text-sm font-medium text-gray-900"><?php echo htmlspecialchars($cert['title']); ?></td>
                                    <td class="px-6 py-4 text-sm">
                                        <?php if (($cert['category'] ?? 'iso') === 'product'): ?>
                                            <span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800">Product</span>
                                        <?php else: ?>
                                            <span class="px-2 py-1 text-xs rounded-full bg-gray-200 text-gray-700">ISO</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500"><?php echo htmlspecialchars($cert['issuing_organization'] ?: '—'); ?></td>
                                    <td class="px-6 py-4 text-sm text-gray-500"><?php echo $cert['issue_date'] ? formatDate($cert['issue_date'], 'M d, Y') : '—'; ?></td>
                                    <td class="px-6 py-4 text-sm">
                                        <?php if ($cert['expiry_date']): ?>
                                            <span class="<?php echo $is_expired ? 'text-red-600 font-medium' : 'text-gray-500'; ?>">
                                                <?php echo formatDate($cert['expiry_date'], 'M d, Y'); ?>
                                                <?php if ($is_expired): ?><i class="fas fa-exclamation-triangle ml-1"></i><?php endif; ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-gray-400">No expiry</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900"><?php echo (int)$cert['display_order']; ?></td>
                                    <td class="px-6 py-4">
                                        <?php if ($cert['is_active']): ?>
                                            <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">Active</span>
                                        <?php else: ?>
                                            <span class="px-2 py-1 text-xs rounded-full bg-red-100 text-red-800">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 text-sm font-medium whitespace-nowrap">
                                        <a href="?action=edit&id=<?php echo $cert['id']; ?>" class="text-primary hover:text-secondary mr-3" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="<?php echo $view_url; ?>" target="_blank" class="text-gray-500 hover:text-gray-700 mr-3" title="View on site">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <form method="POST" action="certifications.php" class="inline" onsubmit="return confirm('Delete this certification? This cannot be undone.');">
                                            <?php echo csrfTokenField(); ?>
                                            <input type="hidden" name="delete_id" value="<?php echo $cert['id']; ?>">
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
                    'total_items'   => $total_certs,
                    'per_page'      => $per_page,
                    'base_query'    => $_GET,
                ]);
                ?>
            </div>

            <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
                <h3 class="font-semibold text-blue-900 mb-2">Quick Links</h3>
                <ul class="text-sm text-blue-800 space-y-1">
                    <li><a href="<?php echo $view_url; ?>" target="_blank" class="hover:underline"><i class="fas fa-external-link-alt mr-1"></i>View Certifications Page</a></li>
                    <li><a href="index.php" class="hover:underline"><i class="fas fa-home mr-1"></i>Back to Admin Dashboard</a></li>
                </ul>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
