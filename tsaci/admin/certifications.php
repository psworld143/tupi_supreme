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
if ($status_param === 'saved')   $success = 'Certification saved successfully!';
if ($status_param === 'deleted') $success = 'Certification deleted successfully!';

// Handle form submissions (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['delete_id'] ?? '') === '') {
    $title = sanitizeInput($_POST['title'] ?? '');
    $issuing_organization = sanitizeInput($_POST['issuing_organization'] ?? '');
    $certificate_number = sanitizeInput($_POST['certificate_number'] ?? '');
    $issue_date = sanitizeInput($_POST['issue_date'] ?? null);
    $expiry_date = sanitizeInput($_POST['expiry_date'] ?? null);
    $description = $_POST['description'] ?? '';
    $image_url = sanitizeInput($_POST['image_url'] ?? '');
    $document_url = sanitizeInput($_POST['document_url'] ?? '');
    $display_order = intval($_POST['display_order'] ?? 0);
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    if (empty($title)) {
        $error = 'Title is required.';
    } else {
        if ($action === 'add') {
            $stmt = $db->prepare("INSERT INTO certifications (title, issuing_organization, certificate_number, issue_date, expiry_date, description, image_url, document_url, display_order, is_active, updated_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssssssiii", $title, $issuing_organization, $certificate_number, $issue_date, $expiry_date, $description, $image_url, $document_url, $display_order, $is_active, $_SESSION['admin_id']);
            if ($stmt->execute()) {
                logActivity('create', 'certifications', $db->insert_id, "Added certification: {$title}");
                redirect('certifications.php?status=saved');
            } else {
                $error = 'Error: ' . $stmt->error;
            }
        } elseif ($action === 'edit' && $id) {
            $stmt = $db->prepare("UPDATE certifications SET title = ?, issuing_organization = ?, certificate_number = ?, issue_date = ?, expiry_date = ?, description = ?, image_url = ?, document_url = ?, display_order = ?, is_active = ?, updated_by = ? WHERE id = ?");
            $stmt->bind_param("sssssssssiiii", $title, $issuing_organization, $certificate_number, $issue_date, $expiry_date, $description, $image_url, $document_url, $display_order, $is_active, $_SESSION['admin_id'], $id);
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
$per_page = 10;

if ($action === 'list') {
    $per_page = 10;
    $current_page_num = max(1, intval($_GET['page'] ?? 1));
    $filter_status = $_GET['filter_status'] ?? '';
    $filter_expiry = $_GET['filter_expiry'] ?? '';
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
    $list_sql = "SELECT * FROM certifications $where_sql ORDER BY display_order, title LIMIT ? OFFSET ?";
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

// All certifications render in the ISO Certifications section on the public page
$view_url = '../certifications.php#iso-certifications';
?>
<!DOCTYPE html>
<html lang="en">
<head>
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
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-2xl font-bold mb-1"><?php echo $action === 'add' ? 'Add New' : 'Edit'; ?> Certification</h2>
                <p class="text-sm text-gray-500 mb-6">Fields marked <span class="text-red-500">*</span> are required.</p>

                <form method="POST" action="">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Title <span class="text-red-500">*</span></label>
                            <input type="text" name="title" required
                                   value="<?php echo htmlspecialchars($edit_cert['title'] ?? ''); ?>"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary"
                                   placeholder="e.g., ISO 9001:2015 Quality Management">
                            <p class="text-xs text-gray-400 mt-1">The certification name shown on the public page.</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Issuing Organization</label>
                            <input type="text" name="issuing_organization"
                                   value="<?php echo htmlspecialchars($edit_cert['issuing_organization'] ?? ''); ?>"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary"
                                   placeholder="e.g., International Organization for Standardization">
                            <p class="text-xs text-gray-400 mt-1">The body that issued the certification (optional).</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Certificate Number</label>
                            <input type="text" name="certificate_number"
                                   value="<?php echo htmlspecialchars($edit_cert['certificate_number'] ?? ''); ?>"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary"
                                   placeholder="e.g., CERT-2024-001">
                            <p class="text-xs text-gray-400 mt-1">The official certificate number (optional).</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Issue Date</label>
                            <input type="date" name="issue_date"
                                   value="<?php echo htmlspecialchars($edit_cert['issue_date'] ?? ''); ?>"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary">
                            <p class="text-xs text-gray-400 mt-1">When the certification was issued (optional).</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Expiry Date</label>
                            <input type="date" name="expiry_date"
                                   value="<?php echo htmlspecialchars($edit_cert['expiry_date'] ?? ''); ?>"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary">
                            <p class="text-xs text-gray-400 mt-1">Leave blank if the certification does not expire.</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Image URL <span class="text-gray-400 font-normal">(optional)</span></label>
                            <input type="url" name="image_url"
                                   value="<?php echo htmlspecialchars($edit_cert['image_url'] ?? ''); ?>"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary"
                                   placeholder="https://example.com/cert-badge.png">
                            <p class="text-xs text-gray-400 mt-1">Badge or certificate image (optional).</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Document URL <span class="text-gray-400 font-normal">(optional)</span></label>
                            <input type="url" name="document_url"
                                   value="<?php echo htmlspecialchars($edit_cert['document_url'] ?? ''); ?>"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary"
                                   placeholder="https://example.com/certificate.pdf">
                            <p class="text-xs text-gray-400 mt-1">Link to the downloadable certificate document (optional).</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Display Order</label>
                            <input type="number" name="display_order" min="0"
                                   value="<?php echo htmlspecialchars($edit_cert['display_order'] ?? 0); ?>"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary">
                            <p class="text-xs text-gray-400 mt-1">Lower numbers appear first.</p>
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Description <span class="text-gray-400 font-normal">(optional)</span></label>
                            <textarea name="description" rows="4"
                                      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary"><?php echo htmlspecialchars($edit_cert['description'] ?? ''); ?></textarea>
                            <p class="text-xs text-gray-400 mt-1">Short description of what the certification covers.</p>
                        </div>

                        <div class="md:col-span-2">
                            <label class="flex items-center cursor-pointer">
                                <input type="checkbox" name="is_active" value="1"
                                       <?php echo ($edit_cert && $edit_cert['is_active']) || !$edit_cert ? 'checked' : ''; ?>
                                       class="sr-only peer">
                                <span class="relative inline-flex items-center">
                                    <span class="w-11 h-6 bg-gray-300 peer-checked:bg-primary rounded-full transition-colors"></span>
                                    <span class="absolute left-0.5 top-0.5 w-5 h-5 bg-white rounded-full transition-transform peer-checked:translate-x-5"></span>
                                </span>
                                <span class="ml-3 text-sm text-gray-700">Active <span class="text-gray-400">(shown on the website)</span></span>
                            </label>
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
                        <?php if (!empty($_GET['filter_status']) || !empty($_GET['filter_expiry']) || !empty($_GET['q'])): ?>
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
                                <td colspan="7" class="px-6 py-12 text-center">
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
